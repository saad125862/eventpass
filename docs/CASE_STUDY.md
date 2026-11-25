# EventPass: case study and CV material

## One-paragraph summary

EventPass is a Laravel 13 ticketing platform: attendees book seats and receive QR passes, organizers run events and scan tickets at the door. The engineering focus was **correctness under concurrency** and an **automated delivery pipeline**: a commit is linted, statically analysed, tested (including a 50-process race against PostgreSQL), containerised, security-scanned and deployed to Render, then verified live by commit SHA, all on free infrastructure.

## Problem → decision → result

| Problem | Decision | Result |
|---|---|---|
| Overselling when buyers race | Row-level locks in a transaction + idempotency keys | CI proves 10 of 50 concurrent buyers win 10 tickets |
| Free tier has no cron or workers | The container supervises its own queue worker and scheduler | Emails, reminders and hold expiry work with zero extra services |
| "Works on my machine" | One multi-stage Dockerfile for CI and production; non-root user | CI smoke test runs the exact production image |
| Render rejected the first deploy (`frankenphp: Operation not permitted`) | Reproduced locally with `docker run --cap-drop=ALL`, removed the binary's file capability | CI now smoke-tests with all capabilities dropped so it can never regress |
| Deploying broken code | Render `autoDeployTrigger: checksPass` + health-check gating | Only green commits ship; failed releases never replace a healthy one |
| "Did my deploy actually land?" | `/health` returns the commit SHA; a scheduled, non-blocking watcher compares it with `main` | A release that fails to land is flagged within 30 minutes |
| Deploy never started: my "verify deployment" workflow was a check Render waited on while it waited on Render | Spotted the circular wait from the commit's check runs; moved verification to a scheduled watcher | Pipeline can no longer deadlock itself |

## Metrics to quote

- 45 automated tests (92% line coverage), run on both SQLite and PostgreSQL
- 4 CI jobs plus a deploy-verification workflow; coverage gate at 80%; Larastan level 5; Trivy critical-vulnerability gate
- 100% free-tier hosting (Render + Neon + GitHub)

## CV bullets (pick 3–4)

- Built **EventPass**, a Laravel 13 event-ticketing platform with QR check-in; engineered **oversell-proof booking** (transactional row locking, idempotency keys, hold expiry) and proved it with a CI test that races **50 parallel processes against PostgreSQL for 10 tickets**.
- Designed a **CI/CD pipeline in GitHub Actions** (Pint, Larastan, coverage gate, Postgres concurrency job, Trivy scan, container smoke test) gating **automatic deployment to Render**, with post-deploy verification that polls `/health` for the released commit SHA.
- **Containerised** the app with a multi-stage, non-root Docker image (FrankenPHP); diagnosed and fixed a production-only start failure by reproducing it with dropped Linux capabilities, then added that check to CI.
- Delivered a **premium UI/UX**: animated landing page, 3D ticket tilt, live checkout countdown, animated card preview, confetti confirmation and a camera-based **QR scanner** with atomic one-scan guarantees.
- Ran the whole system on **free tiers** (Render, Neon, GHCR) by moving queue work and scheduling into the app container.

## Interview talking points

1. **Why lock the ticket-type rows rather than count and insert?** Counting then inserting is a race. Locking serialises buyers per ticket type, and locking in id order avoids deadlocks across multi-type carts.
2. **Why compute availability from "paid + unexpired holds"?** Seats free up the moment a hold lapses, without depending on a cron job being up.
3. **How is a double scan prevented?** A single conditional `UPDATE ... WHERE status = 'valid'`; the affected-row count decides who won.
4. **What would you change for real payments?** Swap `MockGateway` behind the `PaymentGateway` interface for Stripe or SSLCommerz; the signed, idempotent webhook endpoint already exists.
5. **What are the free-tier trade-offs?** Cold starts after 15 minutes idle, and in-container workers stop when the instance sleeps. A paid instance or a separate worker removes both.

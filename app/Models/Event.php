<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $organizer_id
 * @property string $title
 * @property string $slug
 * @property string $category
 * @property string $description
 * @property string $venue
 * @property string $city
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property int $theme
 * @property EventStatus $status
 * @property-read User $organizer
 * @property-read Collection<int, TicketType> $ticketTypes
 */
class Event extends Model
{
    public const CATEGORIES = ['Music', 'Tech', 'Art', 'Food', 'Sports', 'Business', 'Comedy', 'Community'];

    protected $fillable = [
        'organizer_id', 'title', 'slug', 'category', 'description', 'venue', 'city',
        'starts_at', 'ends_at', 'theme', 'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => EventStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    /** @return HasMany<TicketType, $this> */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class)->orderBy('sort')->orderBy('id');
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Published->value);
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now()->startOfDay());
    }

    public function isPublished(): bool
    {
        return $this->status === EventStatus::Published;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Total seats across all ticket types. */
    public function capacity(): int
    {
        return (int) $this->ticketTypes->sum('quantity');
    }

    /** Seats still available (use ticketTypes eager-loaded with withReserved()). */
    public function remaining(): int
    {
        return (int) $this->ticketTypes->sum(fn (TicketType $type) => $type->available());
    }

    /** Lowest ticket price in cents (null when no ticket types). */
    public function minPriceCents(): ?int
    {
        $types = $this->relationLoaded('ticketTypes') ? $this->ticketTypes : $this->ticketTypes()->get();

        return $types->isEmpty() ? null : (int) $types->min('price_cents');
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $i = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}

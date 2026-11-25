<?php

namespace App\Services;

use App\Models\Ticket;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    /** The payload encoded in a ticket's QR code: a public landing URL ending in the token. */
    public function payload(Ticket $ticket): string
    {
        return url('/t/'.$ticket->code);
    }

    /** Render a ticket QR code as an inline SVG string. */
    public function svg(Ticket $ticket, int $size = 220): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));

        return $writer->writeString($this->payload($ticket));
    }

    /** Same QR as a data URI, for PDFs. */
    public function dataUri(Ticket $ticket, int $size = 220): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($ticket, $size));
    }
}

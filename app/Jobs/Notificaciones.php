<?php

namespace App\Jobs;

use App\Mail\VoucherProcesoFallido;
use App\Models\User;
use App\Notifications\VoucherProcessingFailedNotification;
use App\Services\VoucherService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class Notificaciones implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $xmlContents;
    protected $user;
    protected $serie;
    protected $numero;
    protected $tipoComprobante;
    protected $moneda;
    /**
     * Create a new job instance.
     */
    public function __construct(array $xmlContents, User $user, ?string $serie, ?string $numero, ?string $tipoComprobante, ?string $moneda)
    {
        $this->xmlContents = $xmlContents;
        $this->user = $user;
        $this->serie = $serie;
        $this->numero = $numero;
        $this->tipoComprobante = $tipoComprobante;
        $this->moneda = $moneda;
    }

    /**
     * Execute the job.
     */
    public function handle(VoucherService $voucherService): void
    {
        try {
            $voucherService->storeVouchersFromXmlContents(
                $this->xmlContents,
                $this->user,
                $this->serie,
                $this->numero,
                $this->tipoComprobante,
                $this->moneda
            );
        } catch (Exception $exception) {
            Mail::to($this->user->mail)->send(new VoucherProcesoFallido($this->user,$exception->getMessage()));
        }
    }
}

<?php

namespace App\Http\Controllers\Vouchers\Voucher;

use App\Http\Requests\Vouchers\GetVoucherIDRequest;
use App\Http\Requests\Vouchers\GetVouchersRequest;
use App\Http\Resources\Vouchers\VoucherResource;
use App\Services\CambioService;
use App\Services\VoucherService;
use Exception;
use Illuminate\Http\Response;

class GetMontosHandler
{
    public function __construct(private readonly VoucherService $voucherService)
    {
    }

    public function __invoke(): Response
    {
        try {

            $cantidad = $this->voucherService->totalQuantity();


            return response([
                'total en soles' => $cantidad,
                'total en dolares' => CambioService::convertir($cantidad,"USD"),
            ], 200);
            
        } catch (Exception $exception) {
            return response([
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

}

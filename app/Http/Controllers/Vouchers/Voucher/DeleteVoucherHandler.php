<?php

namespace App\Http\Controllers\Vouchers\Voucher;

use App\Http\Requests\Vouchers\GetVoucherIDRequest;
use App\Http\Requests\Vouchers\GetVouchersRequest;
use App\Http\Resources\Vouchers\VoucherResource;
use App\Services\VoucherService;
use Exception;
use Illuminate\Http\Response;

class DeleteVoucherHandler
{
    public function __construct(private readonly VoucherService $voucherService)
    {
    }

    public function __invoke(GetVoucherIDRequest $request): Response
    {
        try {

            $voucher = $this->voucherService->getVoucherByID($request->query('id'));
            $this->voucherService->deleteVoucher($voucher);

            return response([
                'data' => 'Voucher eliminado',
            ], 200);
        } catch (Exception $exception) {
            return response([
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

}

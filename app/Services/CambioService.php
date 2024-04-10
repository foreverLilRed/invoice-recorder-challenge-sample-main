<?php

namespace App\Services;

use App\Events\Vouchers\VouchersCreated;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherLine;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Ramsey\Uuid\Uuid;
use SimpleXMLElement;

class CambioService
{
    // Propiedad estática para almacenar las conversiones de moneda
    public static $monedas = [
        'USD' => 0.27,
    ];

    public static function convertir(float $cantidad = 0, string $moneda)
    {

        if (isset(self::$monedas[$moneda])) {
            return $cantidad * self::$monedas[$moneda];
        } else {
            throw new \Exception("La moneda $moneda aun no tiene conversion");
        }
    }
}

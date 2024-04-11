<?php

namespace App\Services;

use App\Events\Vouchers\VouchersCreated;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherLine;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Ramsey\Uuid\Uuid;
use SimpleXMLElement;

class VoucherService
{
    //Funcion para obtener la suma de los campos "total_amount"
    public function totalQuantity(): float{
        return Voucher::sum('total_amount');
    }
    
    //Funcion para obtener Voucher por ID
    public function getVoucherByID($id): Voucher{
        $partes = explode("-", $id);
        $serie = $partes[0];
        $numero = $partes[1];

        return Voucher::where('serie', $serie)
                    ->where('numero', $numero)
                    ->firstOrFail();
    }

    //Funcion para borrar voucher
    public function deleteVoucher(Voucher $voucher): void{
        $voucher->delete();
    }

    public function getVouchers(?string $serie, ?string $numero,?string $tipo_comprobante,?string $moneda, ?string $fechaInicio, ?string $fechaFin, int $page, int $paginate): LengthAwarePaginator
    {
        //serie, numero y por un rango de fechas
        $query = Voucher::query();

        if ($serie) {
            $query->where('serie', $serie);
        }

        if ($numero) {
            $query->where('numero', $numero);
        }

        if ($tipo_comprobante) {
            $query->where('tipo_comprobante', $tipo_comprobante);
        }

        if ($moneda) {
            $query->where('moneda', $moneda);
        }

        if ($fechaInicio && $fechaFin) {
            $fechaInicio = Carbon::createFromFormat('d-m-Y', $fechaInicio)->startOfDay();
            $fechaFin = Carbon::createFromFormat('d-m-Y', $fechaFin)->endOfDay();
            $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        }

        return $query->with(['lines', 'user'])->paginate(perPage: $paginate, page: $page);
    }

    /**
     * @param string[] $xmlContents
     * @param User $user
     * @return Voucher[]
     */
    public function storeVouchersFromXmlContents(array $xmlContents, User $user, ?string $serie, ?string $numero, ?string $tipo_comprobante, ?string $moneda): array
    {
        $vouchers = [];
        foreach ($xmlContents as $xmlContent) {
            $vouchers[] = $this->storeVoucherFromXmlContent($xmlContent, $user, $serie, $numero, $tipo_comprobante, $moneda);
        }

        VouchersCreated::dispatch($vouchers, $user);

        return $vouchers;
    }

    public function storeVoucherFromXmlContent(string $xmlContent, User $user, ?string $serie, ?string $numero, ?string $tipo_comprobante, ?string $moneda): Voucher
    {
        $xml = new SimpleXMLElement($xmlContent);

        $issuerName = (string) $xml->xpath('//cac:AccountingSupplierParty/cac:Party/cac:PartyName/cbc:Name')[0];
        $issuerDocumentType = (string) $xml->xpath('//cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID/@schemeID')[0];
        $issuerDocumentNumber = (string) $xml->xpath('//cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID')[0];

        $receiverName = (string) $xml->xpath('//cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName')[0];
        $receiverDocumentType = (string) $xml->xpath('//cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID/@schemeID')[0];
        $receiverDocumentNumber = (string) $xml->xpath('//cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID')[0];

        $totalAmount = (string) $xml->xpath('//cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount')[0];

        if ($serie === null && $numero === null) {
            $id = (string) $xml->xpath('//cbc:ID')[0] ?? null;
            if ($id) {
                $parts = explode("-", $id);
                $serie = $parts[0] ?? null;
                $numero = $parts[1] ?? null;
            }
        }
        
        if ($tipo_comprobante === null) {
            $tipo_comprobante = (string) $xml->xpath('//cbc:InvoiceTypeCode')[0] ?? null;
        }
        
        if ($moneda === null) {
            $moneda = (string) $xml->xpath('//cbc:DocumentCurrencyCode')[0] ?? null;
        }$moneda = (string) $xml->xpath('//cbc:DocumentCurrencyCode')[0];

        $voucher = new Voucher([
            'serie' => $serie,
            'numero' => $numero,
            'tipo_comprobante' => $tipo_comprobante,
            'moneda' => $moneda,
            'issuer_name' => $issuerName,
            'issuer_document_type' => $issuerDocumentType,
            'issuer_document_number' => $issuerDocumentNumber,
            'receiver_name' => $receiverName,
            'receiver_document_type' => $receiverDocumentType,
            'receiver_document_number' => $receiverDocumentNumber,
            'total_amount' => $totalAmount,
            'xml_content' => $xmlContent,
            'user_id' => $user->id,
        ]);
        $voucher->save();

        foreach ($xml->xpath('//cac:InvoiceLine') as $invoiceLine) {
            $name = (string) $invoiceLine->xpath('cac:Item/cbc:Description')[0];
            $quantity = (float) $invoiceLine->xpath('cbc:InvoicedQuantity')[0];
            $unitPrice = (float) $invoiceLine->xpath('cac:Price/cbc:PriceAmount')[0];

            $voucherLine = new VoucherLine([
                'name' => $name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'voucher_id' => $voucher->id,
            ]);

            $voucherLine->save();
        }

        return $voucher;
    }
}

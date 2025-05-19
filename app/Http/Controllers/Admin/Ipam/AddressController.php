<?php

namespace App\Http\Controllers\Admin\Ipam;

use App\Actions\Ipam\GenerateAddressesAction;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Transformers\Admin\AddressTransformer;
use App\Transformers\Admin\GeneratedAddressesResultTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

use function min;

class AddressController
{
    public function __construct(private GenerateAddressesAction $generateAddressesAction) {}

    public function index(Request $request, AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock): JsonResponse
    {
        $addresses = QueryBuilder::for($addressBlock->addresses())
            ->with('server', 'addressBlock')
            ->defaultSort('-id')
            ->allowedFilters(
                AllowedFilter::exact('ip'),
                AllowedFilter::exact('server_id')->nullable(),
            )
            ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return fractal($addresses, new AddressTransformer)->parseIncludes($request->include)->respond();
    }

    public function generate(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock): JsonResponse
    {
        $result = $this->generateAddressesAction->execute($addressBlock);

        return fractal($result, new GeneratedAddressesResultTransformer)->respond();
    }

    public function destroy(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock, Address $address): Response
    {
        //  TODO: implement deletes & sync affected servers networking configuration

        return response()->noContent();
    }
}

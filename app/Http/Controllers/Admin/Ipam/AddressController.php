<?php

namespace App\Http\Controllers\Admin\Ipam;

use App\Models\Address;
use App\Models\AddressBlock;
use Illuminate\Http\Response;
use App\Models\AddressBlockGroup;
use Illuminate\Http\JsonResponse;
use App\Transformers\Admin\AddressTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

use function min;

class AddressController
{
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
    public function destroy(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock, Address $address): Response
    {
        //  TODO: implement deletes & sync affected servers networking configuration

        return response()->noContent();
    }
}

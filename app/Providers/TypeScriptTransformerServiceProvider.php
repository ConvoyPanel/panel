<?php

namespace App\Providers;

use App\Models\Address;
use App\Models\Deployment;
use IPLib\Address\AddressInterface;
use IPLib\Range\RangeInterface;
use Spatie\LaravelTypeScriptTransformer\LaravelData\LaravelDataTypeScriptTransformerExtension;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider as BaseTypeScriptTransformerServiceProvider;
use Spatie\TypeScriptTransformer\Formatters\PrettierFormatter;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\GlobalNamespaceWriter;

class TypeScriptTransformerServiceProvider extends BaseTypeScriptTransformerServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            ->extension(new LaravelDataTypeScriptTransformerExtension)
            ->transformer(EnumTransformer::class)
            ->transformDirectories(
                app_path('Data'),
                app_path('Enums'),
            )
            ->outputDirectory(resource_path('scripts/types'))
            ->writer(new GlobalNamespaceWriter('generated.d.ts'))
            ->formatter(PrettierFormatter::class);

        // Types referenced by Data classes that live outside the transformed
        // directories (Eloquent models, third-party interfaces) can't resolve on
        // their own — without these the transform logs "not found in the
        // transformed types" warnings and falls back to `any` anyway. IPLib
        // addresses/ranges serialize to strings; the raw models are passed
        // through untyped.
        $config
            ->replaceType(AddressInterface::class, 'string')
            ->replaceType(RangeInterface::class, 'string')
            ->replaceType(Address::class, 'any')
            ->replaceType(Deployment::class, 'any');
    }
}

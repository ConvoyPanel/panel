<?php

namespace App\Http\Controllers\Admin\Images;

use App\Models\Node;
use App\Services\Anchor\AnchorSchemaService;
use App\Services\Images\OsProfiles;
use Illuminate\Http\Request;

/**
 * Proxmox's own parameter definitions, for the hardware form to build itself
 * from.
 *
 * Shipping this to the client is what keeps the form honest: the selects are
 * populated from PVE's enums, the help text is PVE's own description, and a
 * field the running Proxmox does not accept never appears. Nobody edits JSON,
 * and nobody maintains a second copy of Proxmox's rules by hand.
 */
class ImageSchemaController
{
    public function __invoke(Request $request, AnchorSchemaService $schemas)
    {
        $node = filled($nodeId = $request->query('node_id'))
            ? Node::find($nodeId)
            : null;

        return [
            // Whose rules these are, so the UI can say so rather than implying
            // every node agrees.
            'node_id' => $node?->id,
            'parameters' => $schemas->forNode($node),
            // The panel's own keys. They name slots rather than carrying
            // Proxmox values, so the form has to render them itself.
            'meta_keys' => OsProfiles::META_KEYS,
            'defaults' => collect(['l26', 'win11'])
                ->mapWithKeys(fn (string $ostype) => [$ostype => OsProfiles::defaults($ostype)])
                ->all(),
        ];
    }
}

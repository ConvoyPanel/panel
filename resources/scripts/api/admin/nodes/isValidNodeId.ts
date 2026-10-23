/**
 * Whether a node id is usable in a request path.
 *
 * Forms bind the node select to a string and coerce with `Number`, so before a
 * node is chosen the id is `''` (which coerces to 0) or `NaN`, and callers that
 * default to `-1` pass that straight through. None of those name a node, and a
 * request built from one 404s.
 */
export const isValidNodeId = (nodeId: unknown): nodeId is number =>
    typeof nodeId === 'number' && Number.isInteger(nodeId) && nodeId > 0

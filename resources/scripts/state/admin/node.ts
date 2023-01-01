import getNode from '@/api/admin/nodes/getNode'
import { Node } from '@/api/admin/nodes/getNodes'
import { action, Action, createContextStore, thunk, Thunk } from 'easy-peasy'
import isEqual from 'react-fast-compare'

export interface NodeDataStore {
    data?: Node
    setNode: Action<NodeDataStore, Node>
    getNode: Thunk<NodeDataStore, number>
}

interface NodeStore {
    node: NodeDataStore
    clearNodeState: Action<NodeStore>
}

const node: NodeDataStore = {
    data: undefined,
    setNode: action((state, payload) => {
        if (!isEqual(payload, state.data)) {
            state.data = payload
        }
    }),
    getNode: thunk(async (actions, id) => {
        const node = await getNode(id)

        actions.setNode(node)
    }),
}

export const NodeContext = createContextStore<NodeStore>({
    node,
    clearNodeState: action(state => {
        state.node.data = undefined
    }),
})

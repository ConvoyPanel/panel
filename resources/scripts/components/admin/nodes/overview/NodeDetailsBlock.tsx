import RingCard from '@/components/elements/displays/RingCard';
import { NodeContext } from '@/state/admin/node';
import { formatBytes } from '@/util/helpers';
import { useMemo } from 'react';

const NodeDetailsBlock = () => {
    const node = NodeContext.useStoreState(state => state.node.data!);

    const memory = useMemo(() => {
        const total = formatBytes(node.memory, 2);
        const used = formatBytes(node.memory, 2, total.unit);

        return {used, total}
    }, [node.memory])

    return (<>
        <RingCard title='Memory Allocation' value={memory.used.size} valueLabel={memory.used.unit} total={memory.total.size} totalLabel={memory.total.unit} />
    </>);
}

export default NodeDetailsBlock;
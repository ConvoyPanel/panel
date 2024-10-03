import { Snapshot } from '@/types/snapshot.ts'

interface Props {
    nodeDatum: Snapshot
}

const SnapshotNode = ({ nodeDatum }: Props) => {
    return (
        <g className={'fill-foreground p-3'}>
            <text className={'fill-foreground stroke-0 text-2xl'}>
                {nodeDatum.name}
            </text>
        </g>
    )
}

export default SnapshotNode

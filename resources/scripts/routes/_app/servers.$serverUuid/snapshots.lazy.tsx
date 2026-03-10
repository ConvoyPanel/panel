import { createLazyFileRoute } from '@tanstack/react-router';
import { useEffect, useState } from 'react';
import ReactFlow, {
  Background,
  Controls,
  Edge,
  Node,
  useNodesState,
  useEdgesState,
  Position,
  MarkerType,
  Handle,
  NodeProps,
} from 'reactflow';
import 'reactflow/dist/style.css';
import dagre from 'dagre';
import useSnapshotsSWR from '@/api/servers/snapshots/use-snapshots-swr';
import restoreSnapshot from '@/api/servers/snapshots/restoreSnapshot';
import deleteSnapshot from '@/api/servers/snapshots/deleteSnapshot';
import { Snapshot } from '@/types/snapshot';
import { toast } from 'sonner';
import Skeleton from '@/components/ui/Skeleton';
import CreateSnapshotModal from '@/components/interfaces/Client/Server/Snapshots/CreateSnapshotModal';
import SnapshotDetailSheet from '@/components/interfaces/Client/Server/Snapshots/SnapshotDetailSheet';

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/snapshots')({
  component: Snapshots,
});

const dagreGraph = new dagre.graphlib.Graph();
dagreGraph.setDefaultEdgeLabel(() => ({}));

const nodeWidth = 172;
const nodeHeight = 80;

const SnapshotNode = ({ data }: NodeProps) => {
  return (
    <>
      <Handle type="target" position={Position.Top} className="opacity-0" />
      <div
        className={`relative min-w-[150px] rounded-lg border p-2.5 text-center text-xs cursor-pointer bg-card transition-colors
          ${data.isCurrent ? 'border-primary border-2' : 'border-border'}
          ${data.snapshot.errors ? 'bg-destructive/10 border-destructive' : ''}
        `}
      >
        {data.label}
      </div>
      <Handle type="source" position={Position.Bottom} className="opacity-0" />
    </>
  );
};

const CurrentStateNode = () => {
    return (
        <>
            <Handle type="target" position={Position.Top} className="opacity-0" />
            <div className="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary border border-primary/20 whitespace-nowrap">
                Current State
            </div>
        </>
    )
}

const nodeTypes = {
  snapshot: SnapshotNode,
  'current-state': CurrentStateNode,
};

const getLayoutedElements = (nodes: Node[], edges: Edge[]) => {
  dagreGraph.setGraph({ rankdir: 'TB' });

  nodes.forEach((node) => {
    dagreGraph.setNode(node.id, { width: nodeWidth, height: nodeHeight });
  });

  edges.forEach((edge) => {
    dagreGraph.setEdge(edge.source, edge.target);
  });

  dagre.layout(dagreGraph);

  nodes.forEach((node) => {
    const nodeWithPosition = dagreGraph.node(node.id);
    node.targetPosition = Position.Top;
    node.sourcePosition = Position.Bottom;

    node.position = {
      x: nodeWithPosition.x - nodeWidth / 2,
      y: nodeWithPosition.y - nodeHeight / 2,
    };
  });

  return { nodes, edges };
};

function Snapshots() {
  const { serverUuid } = Route.useParams();
  const { data, isLoading, mutate: refresh } = useSnapshotsSWR(serverUuid);

  const snapshotTree = data?.snapshot;
  const currentSnapshotUuid = data?.currentSnapshotUuid;

  const [nodes, setNodes, onNodesChange] = useNodesState([]);
  const [edges, setEdges, onEdgesChange] = useEdgesState([]);
  const [selectedSnapshot, setSelectedSnapshot] = useState<Snapshot | null>(null);

  useEffect(() => {
    if (snapshotTree) {
      const newNodes: Node[] = [];
      const newEdges: Edge[] = [];

      const traverse = (snap: Snapshot, parentId?: number) => {
        if (!snap || !snap.id) return;

        const isCurrent = snap.uuid === currentSnapshotUuid;

        newNodes.push({
          id: snap.id.toString(),
          data: { label: snap.name, snapshot: snap, isCurrent },
          position: { x: 0, y: 0 },
          type: 'snapshot',
          dragHandle: '.drag-handle-missing',
        });

        if (isCurrent) {
            newNodes.push({
                id: 'current-state',
                data: { label: 'Current State' },
                position: { x: 0, y: 0 },
                type: 'current-state',
                dragHandle: '.drag-handle-missing',
            });

            newEdges.push({
                id: `${snap.id}-current`,
                source: snap.id.toString(),
                target: 'current-state',
                type: 'smoothstep',
                markerEnd: { type: MarkerType.ArrowClosed },
                style: { stroke: 'hsl(var(--primary))' }, // Use primary color for current path
            });
        }

        if (parentId) {
          newEdges.push({
            id: `${parentId}-${snap.id}`,
            source: parentId.toString(),
            target: snap.id.toString(),
            type: 'smoothstep',
            markerEnd: { type: MarkerType.ArrowClosed },
            style: { stroke: 'hsl(var(--border))' }
          });
        }

        if (snap.children) {
          snap.children.forEach((child) => traverse(child, snap.id));
        }
      };

      traverse(snapshotTree);

      const { nodes: layoutedNodes, edges: layoutedEdges } = getLayoutedElements(
        newNodes,
        newEdges
      );

      setNodes(layoutedNodes);
      setEdges(layoutedEdges);
    } else {
        setNodes([]);
        setEdges([]);
    }
  }, [snapshotTree, currentSnapshotUuid, setNodes, setEdges]);

  const onRestore = async () => {
    if (!selectedSnapshot) return;
    try {
      await restoreSnapshot(serverUuid, selectedSnapshot.uuid);
      toast.success('Rollback started');
      refresh();
    } catch (e: any) {
      toast.error(e.response?.data?.message || 'Failed to rollback');
    }
  };

  const onDelete = async () => {
    if (!selectedSnapshot) return;
    try {
      await deleteSnapshot(serverUuid, selectedSnapshot.uuid);
      toast.success('Snapshot deleted');
      setSelectedSnapshot(null);
      refresh();
    } catch (e: any) {
      toast.error(e.response?.data?.message || 'Failed to delete snapshot');
    }
  };

  if (isLoading) {
    return (
      <div className="h-full flex flex-col gap-4 p-4">
        <div className="flex justify-between items-center">
          <Skeleton className="h-8 w-32" />
          <Skeleton className="h-10 w-40" />
        </div>
        <Skeleton className="flex-1 min-h-[500px]" />
      </div>
    );
  }

  return (
    <div className="h-full flex flex-col gap-4 p-4">
      <div className="flex justify-between items-center">
        <h2 className="text-xl font-semibold">Snapshots</h2>
        <div className="flex gap-2">
            <CreateSnapshotModal
                serverUuid={serverUuid}
                onSuccess={refresh}
            />
        </div>
      </div>

      <div className="flex-1 min-h-[500px] border rounded-lg bg-gray-50 relative overflow-hidden touch-none">
        <ReactFlow
          nodes={nodes}
          edges={edges}
          onNodesChange={onNodesChange}
          onEdgesChange={onEdgesChange}
          onNodeClick={(_, node) => setSelectedSnapshot(node.data.snapshot)}
          fitView
          attributionPosition="bottom-right"
          nodesConnectable={false}
          nodesFocusable={false}
          edgesFocusable={false}
          connectOnClick={false}
          edgesUpdatable={false}
          nodeTypes={nodeTypes}
          panOnDrag
          selectionOnDrag={false}
          panOnScroll={false}
          zoomOnPinch
          preventScrolling
          className="h-full w-full"
        >
          <Background color="#ccc" gap={16} />
          <Controls />
        </ReactFlow>

        <SnapshotDetailSheet
            selectedSnapshot={selectedSnapshot}
            onClose={() => setSelectedSnapshot(null)}
            onRestore={onRestore}
            onDelete={onDelete}
        />
      </div>
    </div>
  );
}

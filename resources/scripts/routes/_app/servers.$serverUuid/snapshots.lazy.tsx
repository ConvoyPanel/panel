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
} from 'reactflow';
import 'reactflow/dist/style.css';
import dagre from 'dagre';
import useSnapshotsSWR from '@/api/servers/snapshots/use-snapshots-swr';
import createSnapshot from '@/api/servers/snapshots/createSnapshot';
import restoreSnapshot from '@/api/servers/snapshots/restoreSnapshot';
import deleteSnapshot from '@/api/servers/snapshots/deleteSnapshot';
import { Button } from '@/components/ui/Button';
import { Snapshot } from '@/types/snapshot';
import { toast } from 'sonner';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/Dialog';
import { Form } from '@/components/ui/Form';
import InputForm from '@/components/ui/Forms/InputForm';
import CheckboxForm from '@/components/ui/Forms/CheckboxForm';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import {
  IconArrowBackUp,
  IconLoader2,
  IconPlus,
  IconTrash,
} from '@tabler/icons-react';

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/snapshots')({
  component: Snapshots,
});

const dagreGraph = new dagre.graphlib.Graph();
dagreGraph.setDefaultEdgeLabel(() => ({}));

const nodeWidth = 172;
const nodeHeight = 80;

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

const createSnapshotSchema = z.object({
  name: z.string().min(1, 'Name is required').max(40).regex(/^[a-zA-Z0-9_-]+$/, 'Alphanumeric, dashes, underscores only'),
  description: z.string().max(191).optional(),
  includes_ram: z.boolean().default(false),
});

type CreateSnapshotForm = z.infer<typeof createSnapshotSchema>;

function Snapshots() {
  const { serverUuid } = Route.useParams();
  const { data: snapshotTree, isLoading, mutate: refresh } = useSnapshotsSWR(serverUuid);

  const [nodes, setNodes, onNodesChange] = useNodesState([]);
  const [edges, setEdges, onEdgesChange] = useEdgesState([]);
  const [selectedSnapshot, setSelectedSnapshot] = useState<Snapshot | null>(null);

  useEffect(() => {
    if (snapshotTree) {
      const newNodes: Node[] = [];
      const newEdges: Edge[] = [];

      const traverse = (snap: Snapshot, parentId?: string) => {
        newNodes.push({
          id: snap.id.toString(),
          data: { label: snap.name, snapshot: snap },
          position: { x: 0, y: 0 },
          type: 'default',
          style: {
            border: '1px solid #e2e8f0',
            borderRadius: '8px',
            padding: '10px',
            background: snap.errors ? '#fee2e2' : '#fff',
            borderColor: snap.errors ? '#ef4444' : '#cbd5e1',
            width: 150,
            fontSize: '12px',
            textAlign: 'center',
            cursor: 'pointer'
          },
        });

        if (parentId) {
          newEdges.push({
            id: `${parentId}-${snap.id}`,
            source: parentId,
            target: snap.id.toString(),
            type: 'smoothstep',
            markerEnd: { type: MarkerType.ArrowClosed },
            style: { stroke: '#94a3b8' }
          });
        }

        if (snap.children) {
          snap.children.forEach((child) => traverse(child, snap.id.toString()));
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
  }, [snapshotTree, setNodes, setEdges]);

  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const form = useForm<CreateSnapshotForm>({
    resolver: zodResolver(createSnapshotSchema),
    defaultValues: {
      includes_ram: false,
    },
  });

  const onSubmit = async (data: CreateSnapshotForm) => {
    try {
      await createSnapshot(serverUuid, data);
      toast.success('Snapshot creation started');
      setIsCreateOpen(false);
      form.reset();
      refresh();
    } catch (e: any) {
      toast.error(e.response?.data?.message || 'Failed to create snapshot');
    }
  };

  const onRestore = async () => {
    if (!selectedSnapshot) return;
    try {
      await restoreSnapshot(serverUuid, selectedSnapshot.id);
      toast.success('Rollback started');
      refresh();
    } catch (e: any) {
      toast.error(e.response?.data?.message || 'Failed to rollback');
    }
  };

  const onDelete = async () => {
    if (!selectedSnapshot) return;
    try {
      await deleteSnapshot(serverUuid, selectedSnapshot.id);
      toast.success('Snapshot deleted');
      setSelectedSnapshot(null);
      refresh();
    } catch (e: any) {
      toast.error(e.response?.data?.message || 'Failed to delete snapshot');
    }
  };

  if (isLoading) return <div className="p-4 flex justify-center"><IconLoader2 className="animate-spin" /></div>;

  return (
    <div className="h-full flex flex-col gap-4 p-4">
      <div className="flex justify-between items-center">
        <h2 className="text-xl font-semibold">Snapshots</h2>
        <div className="flex gap-2">
            <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
            <DialogTrigger asChild>
                <Button>
                <IconPlus className="mr-2 h-4 w-4" /> Create Snapshot
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                <DialogTitle>Create Snapshot</DialogTitle>
                <DialogDescription>
                    Create a new snapshot of the current server state.
                </DialogDescription>
                </DialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
                        <InputForm
                            name="name"
                            label="Name"
                            placeholder="snap-1"
                        />
                        <InputForm
                            name="description"
                            label="Description (Optional)"
                        />
                        <CheckboxForm
                            name="includes_ram"
                            label="Include RAM"
                        />
                        <DialogFooter>
                            <Button type="submit" disabled={form.formState.isSubmitting}>
                            {form.formState.isSubmitting && <IconLoader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Create
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
            </Dialog>
        </div>
      </div>

      <div className="flex-1 min-h-[500px] border rounded-lg bg-gray-50 relative overflow-hidden">
        <ReactFlow
          nodes={nodes}
          edges={edges}
          onNodesChange={onNodesChange}
          onEdgesChange={onEdgesChange}
          onNodeClick={(_, node) => setSelectedSnapshot(node.data.snapshot)}
          fitView
          attributionPosition="bottom-right"
        >
          <Background color="#ccc" gap={16} />
          <Controls />
        </ReactFlow>

        {selectedSnapshot && (
            <div className="absolute top-4 right-4 p-4 bg-white border rounded shadow-lg w-72 z-10">
                <div className="flex justify-between items-start mb-2">
                    <h3 className="font-bold text-lg">{selectedSnapshot.name}</h3>
                    <Button variant="ghost" size="sm" onClick={() => setSelectedSnapshot(null)} className="h-6 w-6 p-0">×</Button>
                </div>
                {selectedSnapshot.description && (
                    <p className="text-sm text-gray-600 mb-2">{selectedSnapshot.description}</p>
                )}
                <div className="text-xs text-gray-500 mb-4 space-y-1">
                    <p>Size: {selectedSnapshot.size > 0 ? `${(selectedSnapshot.size).toFixed(2)} MB` : 'Calculating...'}</p>
                    <p>Created: {new Date(selectedSnapshot.createdAt).toLocaleString()}</p>
                    {selectedSnapshot.errors && (
                        <p className="text-red-500 font-medium">Error: {selectedSnapshot.errors}</p>
                    )}
                </div>
                <div className="flex gap-2">
                    <Button size="sm" variant="outline" className="flex-1" onClick={onRestore}>
                        <IconArrowBackUp className="mr-2 h-3 w-3" /> Rollback
                    </Button>
                    <Button size="sm" variant="destructive" className="flex-1" onClick={onDelete}>
                        <IconTrash className="mr-2 h-3 w-3" /> Delete
                    </Button>
                </div>
            </div>
        )}
      </div>
    </div>
  );
}

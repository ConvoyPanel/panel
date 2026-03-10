import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetFooter,
} from '@/components/ui/Sheet';
import { Button } from '@/components/ui/Button';
import { IconArrowBackUp, IconTrash } from '@tabler/icons-react';
import { Snapshot } from '@/types/snapshot';

interface Props {
    selectedSnapshot: Snapshot | null;
    onClose: () => void;
    onRestore: () => void;
    onDelete: () => void;
}

export default function SnapshotDetailSheet({ selectedSnapshot, onClose, onRestore, onDelete }: Props) {
    return (
        <Sheet open={!!selectedSnapshot} onOpenChange={(open) => !open && onClose()}>
            <SheetContent>
                <SheetHeader>
                    <SheetTitle>{selectedSnapshot?.name}</SheetTitle>
                    <SheetDescription>
                        {selectedSnapshot?.description || 'No description provided.'}
                    </SheetDescription>
                </SheetHeader>

                {selectedSnapshot && (
                    <div className="py-4 space-y-4">
                        <div className="text-sm text-gray-500 space-y-1">
                            <p><strong>Size:</strong> {selectedSnapshot.size > 0 ? `${(selectedSnapshot.size).toFixed(2)} MB` : 'Calculating...'}</p>
                            <p><strong>Created:</strong> {new Date(selectedSnapshot.createdAt).toLocaleString()}</p>
                            {selectedSnapshot.errors && (
                                <p className="text-red-500 font-medium">Error: {selectedSnapshot.errors}</p>
                            )}
                        </div>
                    </div>
                )}

                <SheetFooter className="flex-col gap-2 sm:flex-col">
                    <Button variant="outline" onClick={onRestore} className="w-full">
                        <IconArrowBackUp className="mr-2 h-4 w-4" /> Rollback
                    </Button>
                    <Button variant="destructive" onClick={onDelete} className="w-full">
                        <IconTrash className="mr-2 h-4 w-4" /> Delete
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}


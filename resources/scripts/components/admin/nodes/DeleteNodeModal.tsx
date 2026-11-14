import ConfirmationModal from '@/components/elements/ConfirmationModal'

interface Props {
    open: boolean
    name: string
    onConfirm: () => Promise<unknown>
    onClose: () => void
}

const DeleteNodeModal = ({ open, name, onConfirm, onClose }: Props) => (
    <ConfirmationModal
        open={open}
        title={`Delete ${name}?`}
        description="Deleting this node only removes it from Convoy, along with its ISO library and templates, and detaches it from its address pools. Everything on the Proxmox node stays as it is."
        confirmLabel='Delete'
        onConfirm={onConfirm}
        onClose={onClose}
    />
)

export default DeleteNodeModal

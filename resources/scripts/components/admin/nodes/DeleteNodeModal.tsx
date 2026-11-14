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
        description={`${name}, its ISO library and its templates are removed from Convoy, and it's detached from its address pools. Nothing changes on the Proxmox node itself. This can't be undone.`}
        confirmLabel='Delete'
        onConfirm={onConfirm}
        onClose={onClose}
    />
)

export default DeleteNodeModal

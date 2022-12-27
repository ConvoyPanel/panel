import { TemplateGroup } from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import Card from '@/components/elements/Card'
import SortableItem, { ChildrenPropsWithHandle } from '@/components/elements/dnd/SortableItem'
import { DottedButton } from '@/components/servers/backups/BackupRow'
import { classNames } from '@/util/helpers'
import { EyeSlashIcon } from '@heroicons/react/20/solid'
//@ts-ignore
import Dots from '@/assets/images/icons/dots-vertical.svg'
import Menu from '@/components/elements/Menu'
import EditTemplateGroupModal from '@/components/admin/nodes/templates/EditTemplateGroupModal'
import { useState } from 'react'

interface Props {
    group: TemplateGroup
    className?: string
}

const TemplateGroupCard = ({ group, className }: Props) => {
    const [showEditModal, setShowEditModal] = useState(false)

    return (
        <SortableItem overrideZIndex handle id={group.id}>
            {({ attributes, listeners, isDragging }: ChildrenPropsWithHandle) => (
                <Card overridePadding className={classNames('min-h-[9rem] relative', className)} key={group.id}>
                    <EditTemplateGroupModal group={group} open={showEditModal} onClose={() => setShowEditModal(false)} />
                    {isDragging && <div className='inset-0 z-10 absolute bg-accent-200' />}
                    <div
                        className='flex justify-between items-center touch-none pt-3 px-4'
                        {...attributes}
                        {...listeners}
                    >
                        <div className='flex items-center space-x-3'>
                            <p className='font-medium text-foreground select-none'>{group.name}</p>
                            {group.hidden && <EyeSlashIcon title='hidden' className='h-5 w-5 text-foreground' />}
                        </div>
                        <Menu>
                            <Menu.Button>
                                <img
                                    src={Dots}
                                    className='w-4 h-4 min-w-[1rem] dark:invert'
                                    alt='3 vertical dots meant for activating a menu'
                                />
                            </Menu.Button>
                            <Menu.Items>
                                <Menu.Item onClick={() => setShowEditModal(true)}>Edit</Menu.Item>
                                <Menu.Divider />
                                <Menu.Item color='danger'>Delete</Menu.Item>
                            </Menu.Items>
                        </Menu>
                    </div>
                    <div className='px-4 pb-4 relative'>
                        <div className='flex flex-col space-y-3 mt-2'>
                            {group.templates!.map(template => (
                                <div className='px-3 py-2 bg-accent-200 rounded' key={template.id}>
                                    <p className='font-medium text-sm text-foreground'>{template.name}</p>
                                    <p className='description-small !text-xs'>vmid: {template.vmid}</p>
                                </div>
                            ))}

                            <button className='text-sm bg-transparent active:bg-accent-200 sm:hover:bg-accent-100 transition-colors text-foreground border border-accent-400 border-dashed rounded py-2'>
                                New Template
                            </button>
                        </div>
                    </div>
                </Card>
            )}
        </SortableItem>
    )
}

export default TemplateGroupCard

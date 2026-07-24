import TestConnectionButton from '@/features/nodes/components/TestConnectionButton.tsx'
import { ReactNode } from 'react'

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { InputForm, SwitchForm } from '@/components/ui/Forms'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/Popover'

import ProxmoxNodeName from '@/assets/images/proxmox-node-name.png'

const HintPopover = ({ children }: { children: ReactNode }) => (
    <Popover>
        <PopoverTrigger asChild>
            <Button
                variant={'link'}
                type={'button'}
                className={'h-auto px-0 py-0 text-xs'}
            >
                What's this?
            </Button>
        </PopoverTrigger>
        <PopoverContent>{children}</PopoverContent>
    </Popover>
)

interface Props {
    /**
     * `create` requires the token pair and asks for the two privilege
     * confirmations; `edit` treats an empty token as "keep the stored one" and
     * drops the confirmations, which only describe the token being entered.
     */
    mode: 'create' | 'edit'
    /** Present on `edit`, so the test can run against the saved credentials. */
    nodeId?: number
}

const ConnectionSection = ({ mode, nodeId }: Props) => {
    const isEdit = mode === 'edit'

    return (
        <Card className={'@container'}>
            <CardHeader>
                <CardTitle>Connection</CardTitle>
                <CardDescription>
                    {isEdit
                        ? 'The Proxmox API endpoint and token Convoy authenticates with. Leave the token fields blank to keep the stored token.'
                        : 'The Proxmox API endpoint and the token Convoy authenticates with.'}
                </CardDescription>
            </CardHeader>
            <CardContent className={'space-y-4'}>
                {/* Port is fixed at 6rem — it holds four digits, and letting it
                    share the row's free space just leaves it padded with air. */}
                <div
                    className={
                        'grid grid-cols-1 gap-3 @2xl:grid-cols-[1fr_6rem_1fr]'
                    }
                >
                    <InputForm
                        name={'fqdn'}
                        label={'FQDN'}
                        placeholder={'advinservers.com'}
                    />
                    <InputForm name={'port'} label={'Port'} type={'number'} />
                    {/* Shortened from "Proxmox Node Name": in a three-column row
                        the full label collides with the hint trigger sharing its
                        line. The card's description already establishes Proxmox,
                        and the hint spells the field out in full. */}
                    <InputForm
                        name={'name'}
                        label={'Node Name'}
                        labelAction={
                            <HintPopover>
                                <p className={'text-sm'}>
                                    The Proxmox Node Name is the unique
                                    identifier for your Proxmox server. It is
                                    usually the hostname of the server.
                                </p>
                                <img
                                    className={'mt-3 rounded-md'}
                                    alt={
                                        'Proxmox VE interface with the node ‘us-southeast-2’ selected under Datacenter (cluster01).'
                                    }
                                    src={ProxmoxNodeName}
                                />
                            </HintPopover>
                        }
                    />
                </div>

                <div className={'grid grid-cols-1 gap-3 @lg:grid-cols-2'}>
                    <InputForm
                        name={'tokenId'}
                        label={'Token ID'}
                        autoComplete={'off'}
                        placeholder={isEdit ? 'Unchanged' : undefined}
                        labelAction={
                            <HintPopover>
                                <p className={'text-sm'}>
                                    Proxmox API tokens provide secure,
                                    token-based access to your Proxmox VE
                                    installation. They consist of a Token ID and
                                    a Token Secret, which let you authenticate
                                    API requests without your account password.
                                    Generate and manage these in your Proxmox
                                    interface and keep them confidential.
                                </p>
                            </HintPopover>
                        }
                    />
                    <InputForm
                        name={'tokenSecret'}
                        label={'Token Secret'}
                        type={'password'}
                        autoComplete={'off'}
                        placeholder={isEdit ? 'Unchanged' : undefined}
                    />
                </div>

                {/* Padding overrides the Card default: these are switch rows, not
                    a header/content card, so the divider has to span the full
                    width. */}
                <Card className={'divide-y px-3.5'}>
                    {/* Only asked on create: they confirm a property of the
                        token being entered, and on edit there may not be a new
                        token to confirm anything about. */}
                    {!isEdit && (
                        <>
                            <SwitchForm
                                name={'rootPrivileges'}
                                label={'Token has root privileges'}
                                description={
                                    'Convoy needs a token with root privileges to manage this node.'
                                }
                                formItemProps={{ className: 'py-3' }}
                            />
                            <SwitchForm
                                name={'privilegeSeparationDisabled'}
                                label={'Privilege separation disabled'}
                                description={
                                    'Required for the token to inherit root privileges.'
                                }
                                formItemProps={{ className: 'py-3' }}
                            />
                        </>
                    )}
                    <SwitchForm
                        name={'verifyTls'}
                        label={'Verify TLS certificate'}
                        description={'Only disable on a trusted private path.'}
                        formItemProps={{ className: 'py-3' }}
                    />
                </Card>

                <TestConnectionButton nodeId={nodeId} />
            </CardContent>
        </Card>
    )
}

export default ConnectionSection

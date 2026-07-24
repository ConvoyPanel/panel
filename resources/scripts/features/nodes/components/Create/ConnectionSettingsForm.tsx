import ProxmoxNodeName from '@/assets/images/proxmox-node-name.png'
import SectionRow from '@/features/nodes/components/Create/SectionRow.tsx'
import TestConnectionButton from '@/features/nodes/components/Create/TestConnectionButton.tsx'

import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { InputForm, SwitchForm } from '@/components/ui/Forms'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/Popover'

const HintPopover = ({ children }: { children: React.ReactNode }) => (
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

const ConnectionSettingsForm = () => {
    return (
        <SectionRow
            title={'Connection'}
            description={
                'The Proxmox API endpoint and the token Convoy authenticates with.'
            }
        >
            <div className={'grid gap-4 sm:grid-cols-[1fr_6rem]'}>
                <InputForm
                    name={'fqdn'}
                    label={'FQDN'}
                    placeholder={'advinservers.com'}
                />
                <InputForm name={'port'} label={'Port'} type={'number'} />
            </div>

            <InputForm
                name={'name'}
                label={'Proxmox Node Name'}
                labelAction={
                    <HintPopover>
                        <p className={'text-sm'}>
                            The Proxmox Node Name is the unique identifier for
                            your Proxmox server. It is usually the hostname of
                            the server.
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

            <div className={'grid gap-4 sm:grid-cols-2'}>
                <InputForm
                    name={'tokenId'}
                    label={'Token ID'}
                    autoComplete={'off'}
                    labelAction={
                        <HintPopover>
                            <p className={'text-sm'}>
                                Proxmox API tokens provide secure, token-based
                                access to your Proxmox VE installation. They
                                consist of a Token ID and a Token Secret, which
                                let you authenticate API requests without your
                                account password. Generate and manage these in
                                your Proxmox interface and keep them
                                confidential.
                            </p>
                        </HintPopover>
                    }
                />
                <InputForm
                    name={'tokenSecret'}
                    label={'Token Secret'}
                    type={'password'}
                    autoComplete={'off'}
                />
            </div>

            {/* Padding overrides the Card default: these are switch rows, not a
                header/content card, so the divider has to span the full width. */}
            <Card className={'divide-y px-3.5'}>
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
                <SwitchForm
                    name={'verifyTls'}
                    label={'Verify TLS certificate'}
                    description={
                        'Only disable on a trusted private path.'
                    }
                    formItemProps={{ className: 'py-3' }}
                />
            </Card>

            <TestConnectionButton />
        </SectionRow>
    )
}

export default ConnectionSettingsForm

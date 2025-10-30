import { Heading } from '@/components/ui/Typography'
import { CheckboxForm, InputForm } from '@/components/ui/Forms'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/Popover'
import { Button } from '@/components/ui/Button'

import ProxmoxNodeName from '@/assets/images/proxmox-node-name.png'
import TestConnectionButton from '@/components/interfaces/Admin/Node/Create/TestConnectionButton.tsx'

const ConnectionSettingsForm = () => {
    return <div className={'flex flex-col space-y-4'}>
        <Heading as={'h3'}>Connection</Heading>
        <div className={'flex space-x-3'}>
            <InputForm
                name={'fqdn'}
                label={'FQDN'}
                placeholder={'advinservers.com'}
                formItemProps={{ className: 'grow' }}
            />
            <InputForm
                name={'port'}
                label={'Port'}
                type={'number'}
                formItemProps={{
                    className: 'max-w-[5rem]',
                }}
            />
        </div>
        <div className={'flex flex-col'}>
            <Popover>
                <PopoverTrigger asChild>
                    <Button
                        variant={'link'}
                        size={'sm'}
                        className={
                            'absolute self-end px-0 pt-0'
                        }
                    >
                        What's this?
                    </Button>
                </PopoverTrigger>
                <PopoverContent>
                    <p className={'text-sm'}>
                        The Proxmox Node Name is the unique
                        identifier for your Proxmox server.
                        It is usually the hostname of the
                        server.
                    </p>

                    <img
                        className={'mt-3 rounded-md'}
                        alt={
                            'Proxmox VE interface with the node ‘us-southeast-2’ selected under Datacenter (cluster01).'
                        }
                        src={ProxmoxNodeName}
                    />
                </PopoverContent>
            </Popover>
            <InputForm
                name={'name'}
                label={'Proxmox Node Name'}
            />
        </div>
        <div className={'flex flex-col'}>
            <Popover>
                <PopoverTrigger asChild>
                    <Button
                        variant={'link'}
                        size={'sm'}
                        className={
                            'absolute self-end px-0 pt-0'
                        }
                    >
                        What's this?
                    </Button>
                </PopoverTrigger>
                <PopoverContent>
                    <p className={'text-sm'}>
                        Proxmox API tokens provide secure,
                        token-based access to your Proxmox
                        VE installation. They consist of a
                        Token ID and a Token Secret, which
                        allow you to authenticate API
                        requests without using your account
                        password. Generate and manage these
                        tokens in your Proxmox interface and
                        keep them confidential.
                    </p>
                </PopoverContent>
            </Popover>
            <InputForm
                name={'tokenId'}
                label={'Token ID'}
            />
        </div>
        <InputForm
            name={'tokenSecret'}
            label={'Token Secret'}
            type={'password'}
            autoComplete='off'
        />
        <CheckboxForm
            name={'rootPrivileges'}
            label={
                'I granted root privileges to this token'
            }
            description={
                'For Convoy to function correctly, this token must have root privileges. If necessary, you may experiment with more fine-grained permissions.'
            }
        />
        <CheckboxForm
            name={'privilegeSeparationDisabled'}
            label={'I disabled privilege separation'}
            description={
                'Privilege separation must be disabled to enable the token to inherit root privileges.'
            }
        />
        <CheckboxForm
            name={'verifyTls'}
            label={'Verify TLS Certificate'}
            description={
                'Disabling TLS verification can compromise security by exposing you to man-in-the-middle attacks. Only disable this option if you are absolutely certain your environment is secure.'
            }
        />
        <TestConnectionButton />
    </div>
}

export default ConnectionSettingsForm

import getBootOrder from '@/api/server/settings/getBootOrder'
import { Dd, Dt } from '@/components/dashboard/ServerCard'
import Button from '@/components/elements/Button'
import Display from '@/components/elements/displays/DisplayRow'
import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'
import { ServerContext } from '@/state/server'
import { bytesToString } from '@/util/helpers'
import useFlash from '@/util/useFlash'
import useNotify from '@/util/useNotify'
import { useFormik } from 'formik'
import { useEffect, useMemo, useState } from 'react'
import useSWR from 'swr'
import * as yup from 'yup'
import { DndContext, DragEndEvent } from '@dnd-kit/core'
import { arrayMove, SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable'
import SortableItem, { ChildrenPropsWithHandle } from '@/components/elements/dnd/SortableItem'
//@ts-ignore
import DragVerticalIcon from '@/assets/images/icons/drag-vertical.svg'

import { restrictToVerticalAxis, restrictToWindowEdges } from '@dnd-kit/modifiers'
import MessageBox from '@/components/elements/MessageBox'
import { PlusIcon, XMarkIcon } from '@heroicons/react/20/solid'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import updateBootOrder from '@/api/server/settings/updateBootOrder'
import { Disk } from '@/api/server/useServerDetails'
import { Badge } from '@mantine/core'
import MediaContainer from '@/components/servers/settings/MediaContainer'
import BootOrderContainer from '@/components/servers/settings/BootOrderContainer'

const HardwareContainer = () => {
    const server = ServerContext.useStoreState(state => state.server.data!)
    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const notify = useNotify()

    const form = useFormik({
        initialValues: {
            name: server.name,
            hostname: server.hostname,
        },
        validationSchema: yup.object({
            name: yup.string().required('A name is required').max(40),
            hostname: yup
                .string()
                .matches(
                    /((https?):\/\/)?(www.)?[a-z0-9]+(\.[a-z]{2,}){1,3}(#?\/?[a-zA-Z0-9#]+)*\/?(\?[a-zA-Z0-9-_]+=[a-zA-Z0-9-%]+&?)?$/,
                    'Enter a valid hostname'
                ),
        }),
        onSubmit: ({ name, hostname }, { setSubmitting }) => {
            clearFlashes('server:settings:hardware')
        },
    })

    return (
        <>
            <FormCard className='w-full'>
                <form onSubmit={form.handleSubmit}>
                    <FormCard.Body>
                        <FormCard.Title>Hardware</FormCard.Title>
                        <div className='flex flex-col space-y-3 mt-3'>
                            <div className='flex space-x-10 sm:space-x-12'>
                                <dl>
                                    <Dt>CPU</Dt>
                                    <Dd>{server.limits.cpu}</Dd>
                                </dl>
                                <dl>
                                    <Dt>Memory</Dt>
                                    <Dd>{bytesToString(server.limits.memory)}</Dd>
                                </dl>
                                <dl>
                                    <Dt>Disk</Dt>
                                    <Dd>{bytesToString(server.limits.disk)}</Dd>
                                </dl>
                            </div>
                            <div className='flex space-x-10 sm:space-x-12'>
                                <dl>
                                    <Dt>Used Bandwidth</Dt>
                                    <Dd>{bytesToString(server.usages.bandwidth)}</Dd>
                                </dl>
                                <dl>
                                    <Dt>Allotted Bandwidth</Dt>
                                    <Dd>
                                        {server.limits.bandwidth ? bytesToString(server.limits.bandwidth) : 'unlimited'}
                                    </Dd>
                                </dl>
                            </div>

                            <dl>
                                <Dt>IP Addresses</Dt>
                                {server.limits.addresses.ipv4.length === 0 &&
                                server.limits.addresses.ipv6.length === 0 ? (
                                    <Dd>There are no addresses associated with this server.</Dd>
                                ) : (
                                    <Display.Group className='mt-3'>
                                        {server.limits.addresses.ipv4.map(ip => (
                                            <Display.Row key={ip.id} className='grid-cols-1 md:grid-cols-3 text-sm'>
                                                <div>
                                                    <p className='description-small !text-xs'>Address</p>
                                                    <p className='font-semibold text-foreground'>
                                                        {ip.address}/{ip.cidr}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p className='description-small !text-xs'>Gateway</p>
                                                    <p className='text-foreground font-semibold'>{ip.gateway}</p>
                                                </div>
                                                <div>
                                                    <p className='description-small !text-xs'>Mac Address</p>
                                                    <p className='text-foreground font-semibold'>
                                                        {ip.macAddress || 'None'}
                                                    </p>
                                                </div>
                                            </Display.Row>
                                        ))}
                                    </Display.Group>
                                )}
                            </dl>
                        </div>
                    </FormCard.Body>
                </form>
            </FormCard>
            <MediaContainer />
            <BootOrderContainer />
        </>
    )
}

export default HardwareContainer

import { DefaultProps, User } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import { Head, Link, useForm } from '@inertiajs/inertia-react'
import { ArrowLeftIcon, ChevronLeftIcon } from '@heroicons/react/solid'
import {
  Button,
  Code,
  NumberInput,
  Overlay,
  Paper,
  TextInput,
  ActionIcon,
  Tooltip,
  SegmentedControl,
  Select,
  Checkbox,
  MultiSelect,
} from '@mantine/core'
import {
  ChangeEvent,
  FormEvent,
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react'
import { formDataHandler } from '@/util/helpers'
import { CheckCircleIcon } from '@heroicons/react/outline'
import { Inertia } from '@inertiajs/inertia'
import { debounce } from 'lodash'
import getSearchNodes from '@/api/admin/nodes/searchNodes'
import SelectItem from '@/components/SelectItem'
import getSearchUsers from '@/api/admin/users/searchUsers'
import { useQuery } from '@tanstack/react-query'
import getTemplates from '@/api/server/settings/getTemplates'
import getSearchAddresses from '@/api/admin/nodes/addresses/searchAddresses'

interface Props extends DefaultProps {}

interface FormData {
  type: string
  name: string
  node_id?: number
  user_id?: number
  vmid?: number
  template_id?: number
  is_template: boolean
  is_visible: boolean
  addresses: string[]
  cpu: number
  memory: number
  disk: number
}

const Create = ({ auth }: Props) => {
  const { data, setData, post, processing, errors, reset } = useForm<FormData>({
    type: 'new',
    name: '',
    node_id: undefined,
    user_id: undefined,
    vmid: undefined,
    template_id: undefined,
    is_template: false,
    is_visible: false,
    addresses: [],
    cpu: 1,
    memory: 1048576,
    disk: 1048576,
  })

  const dataRef = useRef(data)
  useEffect(() => {
    dataRef.current = data
  }, [data])

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) =>
    formDataHandler(event, setData)

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    post(route('admin.servers'))
  }

  const [nodes, setNodes] = useState<
    {
      label: string
      value: string
      description: string
    }[]
  >([])

  const searchNodes = useCallback(
    debounce(async (query: string) => {
      const { data } = await getSearchNodes(query)
      setNodes(
        data.map((node) => {
          return {
            label: node.name,
            value: node.id.toString(),
            description: node.hostname,
          }
        })
      )
    }, 500),
    []
  )

  const [users, setUsers] = useState<
    {
      label: string
      value: string
      description: string
    }[]
  >([])

  const searchUsers = useCallback(
    debounce(async (query: string) => {
      const { data } = await getSearchUsers(query)
      setUsers(
        data.map((user) => {
          return {
            label: user.name,
            value: user.id.toString(),
            description: user.email,
          }
        })
      )
    }, 500),
    []
  )

  const [ips, setIps] = useState<
    {
      label: string
      value: string
    }[]
  >([])

  const searchIps = useCallback(
    debounce(async (query: string) => {
      const { data: res } = await getSearchAddresses(
        query,
        dataRef.current.node_id as number,
        true
      )
      setIps(
        res.map((ip) => {
          return {
            label: ip.address,
            value: ip.id.toString(),
          }
        })
      )
    }, 500),
    []
  )

  useEffect(() => {
    if (data.node_id) {
      searchIps('')
    }
  }, [data])

  const { data: templateData, status } = useQuery(
    ['templates', data.node_id],
    async () => {
      if (!data.node_id) {
        return []
      }

      const { data: resData } = await getTemplates(data.node_id)
      return resData
    }
  )

  const templates = useMemo(() => {
    if (status === 'success') {
      return templateData.map((template) => ({
        value: template.id as unknown as string,
        label: template.server.name,
      }))
    }

    return []
  }, [data, status])

  return (
    <Authenticated auth={auth} header={<h1 className='h1'>New Server</h1>}>
      <Head title={`Import Node`} />

      <Main>
        <Link
          className='flex items-center space-x-2 p-desc'
          href={route('admin.servers')}
        >
          <ArrowLeftIcon className='w-3 h-3' /> <span>Back</span>
        </Link>
        <h3 className='h3-emphasized'>Create a server.</h3>
        <p className='p-desc'>
          Please follow the steps to configure your server and create it.
        </p>
        <div className='flex flex-col !mt-9 space-y-3'>
          <Paper shadow='xs' className='p-card w-full space-y-3'>
            <h3 className='h3 '>Configure Server</h3>
            <SegmentedControl
              value={data.type}
              onChange={(e) => setData('type', e)}
              data={[
                { label: 'New', value: 'new' },
                { label: 'Existing', value: 'existing' },
              ]}
            />
            <form className='space-y-3' onSubmit={submit}>
              <TextInput
                label='Display Name'
                name='name'
                value={data.name}
                className='mt-1 block w-full'
                onChange={onHandleChange}
                error={errors.name}
                required
              />

              <Select
                label='Node'
                placeholder='Search'
                searchable
                itemComponent={SelectItem}
                clearable
                nothingFound='No options'
                value={data.node_id?.toString()}
                onSearchChange={searchNodes}
                onChange={(e) => setData('node_id', parseInt(e as string))}
                data={nodes}
                error={errors.node_id}
                required
              />

              <Select
                label='User'
                placeholder='Search'
                searchable
                itemComponent={SelectItem}
                clearable
                nothingFound='No options'
                value={data.user_id?.toString()}
                onSearchChange={searchUsers}
                onChange={(e) => setData('user_id', parseInt(e as string))}
                data={users}
                error={errors.user_id}
                required
              />

              <NumberInput
                label='VMID'
                name='vmid'
                placeholder={
                  data.type === 'new'
                    ? 'Leave blank to auto-generate'
                    : 'Enter VMID'
                }
                value={data.vmid}
                className='block w-full'
                onChange={(e) => setData('vmid', e)}
                error={errors.vmid}
                required={data.type === 'existing'}
              />

              {data.type === 'new' && data.node_id ? (
                <>
                  <MultiSelect
                    label='IP Addresses'
                    value={data.addresses}
                    onChange={(e) => setData('addresses', e)}
                    onSearchChange={searchIps}
                    searchable
                    styles={{
                      searchInput: {
                        boxShadow: 'none !important',
                      },
                    }}
                    data={ips}
                    error={errors.addresses}
                  />
                  <Select
                    label='Template'
                    value={data.template_id as unknown as string}
                    onChange={(e) =>
                      setData('template_id', parseInt(e as string))
                    }
                    data={templates}
                    error={errors.template_id}
                    required
                  />
                </>
              ) : (
                ''
              )}

              {data.type === 'new' && (
                <div className='grid sm:grid-cols-3 sm:gap-3 space-y-3 sm:space-y-0'>
                  <NumberInput
                    label='CPU'
                    name='cpu'
                    value={data.cpu}
                    className='block w-full'
                    onChange={(e) => setData('cpu', e as number)}
                    error={errors.cpu}
                  />
                  <NumberInput
                    label='Memory (GB)'
                    name='memory'
                    value={data.memory / 1048576}
                    className='block w-full'
                    onChange={(e) => setData('memory', e as number * 1048576)}
                    error={errors.memory}
                  />
                  <NumberInput
                    label='Disk (GB)'
                    name='disk'
                    value={data.disk / 1048576}
                    className='block w-full'
                    onChange={(e) => setData('disk', e as number * 1048576)}
                    error={errors.disk}
                  />
                </div>
              )}

              {data.type === 'existing' && (
                <Checkbox
                  checked={data.is_template}
                  onChange={(e) => setData('is_template', e.target.checked)}
                  label='Mark as template'
                />
              )}

              {data.type === 'existing' && data.is_template ? (
                <Checkbox
                  checked={data.is_visible}
                  onChange={(e) => setData('is_visible', e.target.checked)}
                  label='Make template visible'
                />
              ) : (
                ''
              )}

              <Button
                className='!mt-9'
                type='submit'
                loading={processing}
                fullWidth
              >
                Deploy
              </Button>
            </form>
          </Paper>
        </div>
      </Main>
    </Authenticated>
  )
}

export default Create

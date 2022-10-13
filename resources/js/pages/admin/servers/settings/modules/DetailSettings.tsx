import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/admin/servers/settings/Index'
import { formDataHandler } from '@/util/helpers'
import { useForm } from '@inertiajs/inertia-react'
import { NumberInput, TextInput } from '@mantine/core'
import { ChangeEvent, FormEvent, useContext } from 'react'

const DetailSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const { data, setData, put, processing, errors, transform } = useForm({
    cpu: settingsContext!.details.limits.cpu,
    memory: settingsContext!.details.limits.memory,
    disk: settingsContext!.details.limits.disk,
    snapshot_limit: settingsContext!.details.limits.snapshot_limit,
    backup_limit: settingsContext!.details.limits.backup_limit,
    bandwidth_limit: settingsContext!.details.limits.bandwidth_limit,
  })

  const submit = (e: FormEvent<HTMLFormElement>) => {
    // @ts-expect-error: transforming will result in mismatched types
    transform(data => ({
        limits: {
            cpu: data.cpu,
            memory: data.memory,
            disk: data.disk,
            snapshot_limit: data.snapshot_limit,
            backup_limit: data.backup_limit,
            bandwidth_limit: data.bandwidth_limit,
        },
    }))

    put(route('admin.servers.show.settings.details', settingsContext?.server.id))
  }

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) =>
    formDataHandler(event, setData)

  return (
    <FormBlock
      title='Server Details'
      onSubmit={submit}
      processing={processing}
      defaultAction
      inputs={
        <div className='flex flex-col space-y-3'>
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
            value={data.memory / 1073741824}
            className='block w-full'
            onChange={(e) => setData('memory', (e as number) * 1073741824)}
            error={errors.memory}
          />
          <NumberInput
            label='Disk (GB)'
            name='disk'
            value={data.disk / 1073741824}
            className='block w-full'
            onChange={(e) => setData('disk', (e as number) * 1073741824)}
            error={errors.disk}
          />
          <TextInput
            label='Snapshot Limit'
            name='snapshot_limit'
            value={data.snapshot_limit}
            onChange={onHandleChange}
            placeholder='Unlimited'
            error={errors.snapshot_limit}
          />
          <TextInput
            label='Backup Limit'
            name='backup_limit'
            value={data.backup_limit}
            onChange={onHandleChange}
            placeholder='Unlimited'
            error={errors.backup_limit}
          />
          <NumberInput
            label='Bandwidth (GB)'
            name='bandwidth_limit'
            value={data.bandwidth_limit ? data.bandwidth_limit / 1073741824 : data.bandwidth_limit}
            className='block w-full'
            placeholder='Unlimited'
            onChange={(e) => setData('bandwidth_limit', (e as number) * 1073741824)}
            error={errors.bandwidth_limit}
          />
        </div>
      }
    />
  )
}

export default DetailSettings

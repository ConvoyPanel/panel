import { ServerContext } from '@/state/server'
import { capitalize, convertTimeToSmallest, formatBytes } from '@/util/helpers'
import styled from '@emotion/styled'
import { Skeleton } from '@mantine/core'
import { useEffect, useMemo, useRef } from 'react'
import tw from 'twin.macro'

export const StatRow = styled.div`
  ${tw`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 border border-[#eaeaea] dark:border-[#333333] shadow dark:shadow-none rounded-lg bg-white dark:bg-black`}

  &>div {
    ${tw`border-[#eaeaea] dark:border-[#333333] p-6`}
  }

  & > div > p {
    ${tw`font-semibold text-sm text-black dark:text-stone-400`}
  }

  & > div:not(:last-child) {
    ${tw`md:border-r border-[#eaeaea] dark:border-[#333333]`}
  }

  & > div:nth-of-type(-n + 2) {
    ${tw`border-b lg:border-b-0`}
  }

  & > div:nth-of-type(2) {
    ${tw`md:border-r-0 lg:border-r`}
  }

  & > div:nth-of-type(3) {
    ${tw`border-b md:border-b-0`}
  }

  & > div > p:nth-of-type(2) {
    ${tw`text-2xl font-semibold mt-1 dark:text-white`}
  }
`

const ServerDetailsBlock = () => {
  const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid)
  const memoryLimit = ServerContext.useStoreState(
    (state) => state.server.data!.limits.memory
  )
  const status = ServerContext.useStoreState((state) => state.status.data)
  const getStatus = ServerContext.useStoreActions(
    (actions) => actions.status.getStatus
  )
  const isUpdating = useRef(true)

  useEffect(() => {
    isUpdating.current = true

    const update = async () => {
      if (!isUpdating.current) return

      await getStatus(uuid)

      setTimeout(update, 1000)
    }

    update()

    return () => {
      isUpdating.current = false
    }
  }, [])

  const memory = useMemo(
    () => ({
      used: formatBytes(status?.memory || 0),
      total: formatBytes(memoryLimit),
    }),
    [status]
  )

  const uptime = useMemo(
    () => convertTimeToSmallest(status?.uptime || 0),
    [status]
  )

  return (
    <div>
      {!status ? (
        <Skeleton className='w-full !h-[421px] md:!h-[211px] lg:!h-[106px]' />
      ) : (
        <StatRow>
          <div>
            <p>Server State</p>
            <div className='flex space-x-2 items-center mt-1'>
              <div className='grid place-items-center h-full'>
                <div
                  className={`w-3 h-3 rounded-full ${
                    status.state === 'running' ? 'bg-green-500' : 'bg-red-500'
                  }`}
                ></div>
              </div>
              <p className='text-2xl font-semibold dark:text-white'>
                {capitalize(status.state)}
              </p>
            </div>
          </div>
          <div>
            <p>CPU Usage</p>
            <p>{Math.floor(status.cpu * 100)}%</p>
          </div>
          <div>
            <p>Memory Usage</p>
            <div className='flex space-x-2 items-end mt-1'>
              <p className='text-2xl font-semibold dark:text-white'>
                {memory.used.size} {memory.used.unit}
              </p>
              <p className='text-sm font-semibold description mb-[0.3rem]'>
                / {memory.total.size} {memory.total.unit}
              </p>
            </div>
          </div>
          <div>
            <p>Uptime</p>
            <div className='flex space-x-2 items-end mt-1'>
              <p className='text-2xl font-semibold dark:text-white'>
                {Math.floor(uptime.time)}
              </p>
              <p className='text-sm font-semibold description mb-[0.3rem]'>
                {uptime.unit}
              </p>
            </div>
          </div>
        </StatRow>
      )}
    </div>
  )
}

export default ServerDetailsBlock

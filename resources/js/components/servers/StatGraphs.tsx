import { useContext, useEffect, useState } from 'react'
import { ServerContext } from '@/pages/servers/Show'

import { Line } from 'react-chartjs-2'
import { useChartTickLabel } from '@/util/chart'
import ChartBlock from '@/components/ChartBlock'
import useServerState from '@/util/useServerState'
import { formatBytes, Sizes } from '@/api/server/getStatus'

const StatGraphs = () => {
  const serverContext = useContext(ServerContext)
  const { serverState } = useServerState(serverContext?.server.id as number)

  const cpu = useChartTickLabel('CPU', 100, '%', 2)
  const memory = useChartTickLabel('Memory', serverState?.maxmem.size || 1, serverState?.maxmem.unit || 'GB', 2)

  useEffect(() => {
    if (!serverState) {
        cpu.push(0)
        memory.push(0)

        return
    }

    cpu.push(serverState.cpu)
    memory.push(formatBytes(serverState.memUnparsed.mem, 2, serverState.maxmem.unit as Sizes).size)
  }, [serverState])

  return (
    <div>
      <h3 className='h3-deemphasized'>Graphs</h3>
      <div className='grid grid-cols-2 gap-3 mt-3'>
        <ChartBlock title={'CPU Load'}>
          <Line {...cpu.props} />
        </ChartBlock>
        <ChartBlock title={'Memory'}>
          <Line {...memory.props} />
        </ChartBlock>
      </div>
    </div>
  )
}

export default StatGraphs

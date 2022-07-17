import { useContext, useEffect, useState } from 'react'
import { ServerContext } from '@/pages/servers/Show'

import { Line } from 'react-chartjs-2'
import { useChartTickLabel } from '@/util/chart'
import ChartBlock from '@/components/ChartBlock'
import useServerState from '@/util/useServerState'

const StatGraphs = () => {
  const serverContext = useContext(ServerContext)
  const { serverState } = useServerState(serverContext?.server.id as number)

  const cpu = useChartTickLabel('CPU', 100, '%', 2)
  let memory = useChartTickLabel('Memory', 1, 'GB', 2)
  const [initMemory, setInitMemory] = useState(false)

  useEffect(() => {
    if (!serverState) {
        cpu.push(0)
        memory.push(0)

        return
    }

    /* if (!initMemory) {
        memory = useChartTickLabel('Memory', serverState.maxmem.size, serverState.maxmem.unit)
        setInitMemory(true)
    } */

    cpu.push(serverState.cpu)
    memory.push(serverState.mem.size)
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

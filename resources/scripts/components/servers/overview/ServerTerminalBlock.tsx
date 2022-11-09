import Card from '@/components/elements/Card'
import { ArrowTopRightOnSquareIcon } from '@heroicons/react/20/solid'
import { ActionIcon, Button, UnstyledButton } from '@mantine/core'

const ServerTerminalBlock = () => {
  return (
    <Card className='flex flex-col col-span-10 md:col-span-5'>
      <h5 className='h5'>Terminal</h5>
      <p className='description-small mt-1'>
        Remotely manage your server from the web.
      </p>
      <div className='grid gap-6 grid-cols-2 mt-6'>
        <div className='flex flex-col justify-between border border-colors p-3 rounded'>
          <div>
            <h6 className='h6'>noVNC</h6>
            <p className='description-small mt-1'>
              Best for compatibility. Though not featureful or performant.
            </p>
          </div>
          <Button.Group className='mt-6'>
            <Button className='grow' variant='outline'>Launch</Button>
            <Button variant='outline'>
              <ArrowTopRightOnSquareIcon className='w-4 h-4' />
            </Button>
          </Button.Group>
        </div>
        <div className='flex flex-col justify-between border border-colors p-3 rounded'>
          <div>
            <h6 className='h6'>xTerm.js</h6>
            <p className='description-small mt-1'>Best for performance.</p>
          </div>
          <Button.Group className='mt-6'>
            <Button variant='outline' className='grow'>
              Launch
            </Button>
            <Button variant='outline'>
              <ArrowTopRightOnSquareIcon className='w-4 h-4' />
            </Button>
          </Button.Group>
        </div>
      </div>
    </Card>
  )
}

export default ServerTerminalBlock

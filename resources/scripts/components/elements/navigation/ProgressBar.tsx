import { useStoreActions, useStoreState } from '@/state'
import { randomInt } from '@/util/helpers'
import { useEffect, useRef, useState } from 'react'
import styled from '@emotion/styled'
import { CSSTransition } from 'react-transition-group'
import tw from 'twin.macro'

const BarFill = styled.div`
  ${tw`h-[3px] bg-blue-400`};
  transition: 250ms ease-in-out;
`

type Timer = ReturnType<typeof setTimeout>

const ProgressBar = () => {
  const interval = useRef<Timer>(null) as React.MutableRefObject<Timer>
  const timeout = useRef<Timer>(null) as React.MutableRefObject<Timer>
  const [visible, setVisible] = useState(false)
  const progress = useStoreState((state) => state.progress.progress)
  const continuous = useStoreState((state) => state.progress.continuous)
  const setProgress = useStoreActions((actions) => actions.progress.setProgress)

  useEffect(() => {
    return () => {
      timeout.current && clearTimeout(timeout.current)
      interval.current && clearInterval(interval.current)
    }
  }, [])

  useEffect(() => {
    setVisible((progress || 0) > 0)

    if (progress === 100) {
      timeout.current = setTimeout(() => setProgress(undefined), 500)
    }
  }, [progress])

  useEffect(() => {
    if (!continuous) {
      interval.current && clearInterval(interval.current)
      return
    }

    if (!progress || progress === 0) {
      setProgress(randomInt(20, 30))
    }
  }, [continuous])

  useEffect(() => {
    if (continuous) {
      interval.current && clearInterval(interval.current)
      if ((progress || 0) >= 90) {
        setProgress(90)
      } else {
        interval.current = setTimeout(
          () => setProgress((progress || 0) + randomInt(1, 5)),
          500
        )
      }
    }
  }, [progress, continuous])

  return (
    <div className='w-full fixed h-[3px]'>
      <CSSTransition
        timeout={150}
        appear
        in={visible}
        unmountOnExit
        classNames={'fade'}
      >
        <BarFill
          style={{ width: progress === undefined ? '100%' : `${progress}%` }}
        />
      </CSSTransition>
    </div>
  )
}

export default ProgressBar

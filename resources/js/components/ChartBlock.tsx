import { Paper } from '@mantine/core'
import React from 'react'

interface ChartBlockProps {
  title: string
  legend?: React.ReactNode
  children: React.ReactNode
}

export default ({ title, legend, children }: ChartBlockProps) => (
  <Paper shadow='xs' className='p-card'>
    <h3 className='h4 !text-lg'>{title}</h3>
    {legend && <p className={'text-sm flex items-center'}>{legend}</p>}
    <div className={'z-10 ml-2 mt-3'}>{children}</div>
  </Paper>
)

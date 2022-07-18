export interface Snapshot {
  name: string
  description: string
  digest?: string
  running?: number
  snaptime?: number
  vmstate?: number
  parent?: string
}

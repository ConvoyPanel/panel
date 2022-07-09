import { Config, Router } from 'ziggy-js'

declare global {
  function route(): {
    current: (name: string) => boolean
  }
  function route(
    name?: string,
    params?: any,
    absolute?: boolean,
    customZiggy?: Config
  ): string
}
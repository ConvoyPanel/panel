import { Config, Router } from 'ziggy-js'

declare global {
  function route(): {
    current: (name?: string) => boolean | string
  }
  function route(
    name?: string,
    params?: any,
    absolute?: boolean,
    customZiggy?: Config
  ): string
}
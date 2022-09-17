import { Config, Router } from 'ziggy-js'
import axios from 'axios'
import Pusher from 'pusher-js'
import Echo from 'laravel-echo'

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

  interface Window {
    axios: Axios
    Pusher: typeof Pusher
    Echo: Echo
  }
}
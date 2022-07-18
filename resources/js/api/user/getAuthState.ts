import axios, { AxiosResponse } from 'axios'

export default async () => {
  try {
    const response = await axios.get(route('verify-auth-state'))

    if (response.status === 200) {
      return true
    } else if (response.status === 401) {
      return false
    } else {
      return true
    }
  } catch (error: any) {
    if (error.response.status === 200) {
      return true
    } else if (error.response.status === 401) {
      return false
    } else {
      return true
    }
  }
}

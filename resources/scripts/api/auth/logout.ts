import axios from '@/lib/axios.ts'

const logout = async () => {
    await axios.post('/logout')
}

export default logout

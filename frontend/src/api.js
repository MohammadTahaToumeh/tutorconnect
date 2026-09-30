import axios from 'axios'

axios.defaults.headers.common.Accept = 'application/json'

export function setApiToken(token) {
    if (token) {
        axios.defaults.headers.common.Authorization = `Bearer ${token}`
        return
    }

    delete axios.defaults.headers.common.Authorization
}

const storedToken =
    localStorage.getItem('token')
    || sessionStorage.getItem('token')

setApiToken(storedToken)

export default axios



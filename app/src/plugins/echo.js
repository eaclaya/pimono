import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import http from '@/services/http'

window.Pusher = Pusher

const token = localStorage.getItem('token')

const echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  forceTLS: true,
  // REMOVE wsHost/wsPort if you’re using Pusher Cloud
  authorizer: (channel) => ({
    authorize: (socketId, callback) => {
      http.post(
        `${import.meta.env.VITE_API_URL}broadcasting/auth`,
        {
          socket_id: socketId,
          channel_name: channel.name,
        },
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      )
      .then(response => callback(false, response.data))
      .catch(error => callback(true, error))
    },
  }),
})

window.Echo = echo

export default {
  install: (app) => {
    app.config.globalProperties.$echo = echo
  }
}
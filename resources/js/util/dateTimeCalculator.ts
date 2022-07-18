// Argument must be unix time stamp

export default (timestamp: number) => {
  let months = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
  ]

  let date = new Date(timestamp * 1000)

  let year = date.getFullYear()
  let month = months[date.getMonth()]
  let day = date.getDate()
  let hours = date.getHours()
  let minutes = date.getMinutes()
  let seconds = date.getSeconds()

  return { year, month, day, hours, minutes, seconds }
}

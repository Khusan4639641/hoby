const regEx = /^[\+]{0,1}(?:998)?[\s-]*[\(]{0,1}([0-9]{2})[\)]{0,1}[\s-]*([0-9]{3})[\s-]*([0-9]{2})[\s-]*([0-9]{2})$/

export function parse(rawPhone) {
  if(typeof rawPhone !== 'string') return null

  const phoneData = rawPhone.match(regEx)

  if(!phoneData) return null

  phoneData.shift()
  return '998' + phoneData.join('')
}

// TODO: Распределить на две функции (Greydius)
// export function format(rawPhone) {
//   const phoneData = parse(rawPhone)

//   return phoneData ? '998' + phoneData.join() : null
// }

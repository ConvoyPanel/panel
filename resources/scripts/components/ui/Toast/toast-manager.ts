import { Toast as ToastPrimitive } from '@base-ui/react/toast'

// The manager lives in its own module so call sites can queue a toast without
// pulling in the React tree that renders one. `<Toaster />` binds this same
// instance, which is what lets `toast.add()` work outside a component.
const toast = ToastPrimitive.createToastManager()

export default toast

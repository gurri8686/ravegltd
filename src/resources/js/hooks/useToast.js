import { useEffect } from "react";
import { toast } from "react-toastify";

/**
* @usage
* import { ToastContainer } from 'react-toastify';
* import { useToast } from "./../hooks/useToast";
* const { notifySuccess, notifyError } = useToast();
*/

export const useToast = () => {
  const defaultOptions = {
    position: "top-right",
    autoClose: 3000,
    hideProgressBar: false,
    closeOnClick: true,
    // Mobile: don't pause the auto-close timer when the keyboard closes / window
    // loses focus or on touch-hover, otherwise the toast sticks and never hides.
    pauseOnHover: false,
    pauseOnFocusLoss: false,
    draggable: true,
    progress: undefined,
    theme: "light",
  };

  // Listen for dismiss-all-toasts event (fired on tab switch)
  useEffect(() => {
    const handler = () => toast.dismiss();
    window.addEventListener('dismiss-all-toasts', handler);
    return () => window.removeEventListener('dismiss-all-toasts', handler);
  }, []);

  const notifySuccess = (message) => toast.success(message, defaultOptions);
  const notifyError = (message) => toast.error(message, defaultOptions);
  const notifyInfo = (message) => toast.info(message, defaultOptions);
  const notifyWarning = (message) => toast.warn(message, defaultOptions);

  return { notifySuccess, notifyError, notifyInfo, notifyWarning };
};

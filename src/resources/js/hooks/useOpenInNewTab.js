import { useCallback } from "react";

const useOpenInNewTab = () => {
  const openUrl = useCallback((url, params = {}) => {
    const queryString = new URLSearchParams(params).toString();
    const finalUrl = queryString ? `${url}?${queryString}` : url;

    window.open(finalUrl, "_blank", "noopener,noreferrer");
  }, []);

  return openUrl;
};

export default useOpenInNewTab;

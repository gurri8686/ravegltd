// AlertContext.js
import React, { createContext, useContext, useState } from "react";
import { Modal, Button } from "react-bootstrap";

const AlertContext = createContext();

export function AlertProvider({ children }) {
  const [alert, setAlert] = useState(null);

  const showAlert = (message, variant = "danger") => {
    setAlert({ message, variant });
  };

  const hideAlert = () => setAlert(null);

  return (
    <AlertContext.Provider value={{ showAlert, hideAlert }}>
      {children}

      {/* Bootstrap Modal */}
      <Modal show={!!alert} onHide={hideAlert} centered>
        <Modal.Header>
          <Modal.Title>
            {alert?.variant === "danger" ? <b className="text-danger">Error</b> : <b className="textt-success">Message</b>}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p style={{ margin: 0 }}>{alert?.message}</p>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={hideAlert}>
            Close
          </Button>
        </Modal.Footer>
      </Modal>
    </AlertContext.Provider>
  );
}

export function useAlert() {
  return useContext(AlertContext);
}

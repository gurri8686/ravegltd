import React, { useState } from 'react';
import Alert from 'react-bootstrap/Alert';
import Button from 'react-bootstrap/Button';

function Alerts(props) {
    const [show, setShow] = useState(true);

    return (
        <>
            <Alert className='mt-3' show={show} variant={props.variant}>
                {/*<Alert.Heading>{props.title}</Alert.Heading>*/}
                <p style={{marginBottom:'0px'}}>{props.message}</p>
                {/*<hr />
                <div className="d-flex justify-content-end">
                    <Button onClick={() => setShow(false)} variant="outline-light">
                        Close!
                    </Button>
                </div>*/}
            </Alert>
        </>
    );
}

export default Alerts;
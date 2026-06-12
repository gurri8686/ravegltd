import React, { useState, useRef, useEffect } from "react";
import { useSelector, useDispatch } from 'react-redux'
import * as Yup from "yup"
import { useParams } from "react-router-dom"
import Button from 'react-bootstrap/Button';
import Form from 'react-bootstrap/Form';
import Modal from 'react-bootstrap/Modal';
import { getIn, Formik, Field, useField, useFormik } from 'formik';

import { ValidationError,Alerts, NotFound, Loading } from "."
import { ActionsService } from "../services"
import { Roles, Permissions } from "./../elements";

function RolesAndPermissions(props) {
    let {action, id} = useParams()
    const [show, setShow] = useState(false);
    const [permits, setPermits] = useState([]);
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(0);
    const [success, setSuccess] = useState(0);
    const handleClose = () => { setFieldValue("role",""); setShow(false);}
    const handleShow = () => setShow(true);

    const load = () => {
        ActionsService.allRolesPermissions().then(response => {
            if (response.data.success === true) {
                setData(response.data.payload)
            }
        }).catch(error => {
        });
    }

    useEffect(() => {
        load()
    }, [""]);

    const formik = useFormik({
        initialValues: {
            role: ""
        },
        validationSchema: Yup.object().shape({
            role: Yup.number().required("Required")
        }),
        onSubmit: (values, { resetForm }) => {
            //console.log('Ready To Submit:');
            //console.log(values);
            setLoading(1)
            ActionsService.saveRolePermissions(values.role,typeof id == "undefined" ? 0 : id, values.permission).then(response => {
                if (response.data.success === true) {
                    setLoading(0)
                    setSuccess(1)
                    window.setTimeout(()=>{setSuccess(0)}, 2000)
                }
            }).catch(error => {
                setLoading(0)
            });
            resetForm
        }
    });

    const { handleSubmit, values, setFieldValue, errors } = formik;

    const setRoles = (id) => {
        setFieldValue('role', id)
        setFieldValue('permission',[])
    }

    const rolePermissions = (p) => {
        setPermits(p)
        setFieldValue('permission',p)
    }

    const setPermissions = (type, id) => {
        if(type == 'add'){
            let _permits = permits
            _permits.push({name: id})
            setPermits(_permits)
            setFieldValue('permission',_permits)
        }
        if(type == 'remove'){
            let _newPermits = []
            for(let i in permits){
                if(id == permits[i].name){
                }else{
                    _newPermits.push(permits[i])
                }
            }
            setPermits(_newPermits)
            setFieldValue('permission',_newPermits)
        }
    }

    return (<>
        <Button variant="primary" onClick={handleShow}>
            Set Permissions
        </Button>

        <Modal className="modal-90w" show={show} onHide={handleClose}>
            <Modal.Header>
                <Modal.Title>Roles Permissions <span className="text-uppercase">: {action} </span> {id}</Modal.Title>
            </Modal.Header>
            <Modal.Body>

                <Form onSubmit={handleSubmit}>
                    <Form.Group className="mb-3" controlId="forProject">
                        <Form.Label>Select Role <strong>*</strong></Form.Label><ValidationError formik={formik} name="role" />
                        {typeof data.roleHierarchy != "undefined" ? <Roles {...props} role={setRoles} selectedPermissions={rolePermissions} items={data.roleHierarchy} /> : <></>}
                    </Form.Group>

                    {values.role != ""
                    ?
                    <>
                    <Form.Group className="mb-3" controlId="forProject">
                        <Form.Label>Permissions <strong>*</strong></Form.Label>
                        {typeof data.extraRoutes != "undefined" ? <Permissions {...props} permits={permits} permission={setPermissions} items={{key : 'actions', routes : data.extraRoutes.actions}} /> : <></>}
                    </Form.Group>
                    {
                    loading == 0
                        ?
                        <>
                        <Button variant="secondary" onClick={handleClose}>
                            Close
                        </Button>&nbsp;
                        <Button variant="primary" type="sybmit">
                            Save Changes
                        </Button>
                        </>
                        :
                        <><Loading /></>
                    }
                    {success == 1 ? <Alerts variant="success" title="Access Info!!" message="Saved Successfully!" /> : <></>}    
                    </>
                    :
                    <></>
                    }
                </Form>
            </Modal.Body>
        </Modal>
    </>)
}

export default RolesAndPermissions;
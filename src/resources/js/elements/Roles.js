import React, { useState,useEffect } from 'react'
import Form from 'react-bootstrap/Form'
import { useParams } from "react-router-dom"
import { ActionsService } from "../services"

function Roles(props) {
    const {items, role, selectedPermissions} = props;
    let {action, id} = useParams()

    useEffect(() => {
        console.log('Roles loaded')
    }, []);
    
    const handleChange = (event) => {
        role(event.target.value);
        ActionsService.getRolePermissions(event.target.value, typeof id == "undefined" ? 0 : id).then(response => {
            if (response.data.success === true) {
                selectedPermissions(response.data.payload.permissions)
            }
        }).catch(error => {
        });
    }

    return (
        <ul style={{listStyle:'none'}}>
            {items.map((item) => {
                return (
                    <li key={item.name}>
                        <Form.Check type="radio" onChange={handleChange} name="role" id={item.id} value={item.id} label={item.name} />
                        {item.child_roles && <Roles selectedPermissions={selectedPermissions} role={role} items={item.child_roles} />}
                    </li>
                );
            })}
        </ul>
    );
}

export default Roles;
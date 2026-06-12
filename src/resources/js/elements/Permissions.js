import React, { useState } from 'react'
import Form from 'react-bootstrap/Form'
import { useParams } from "react-router-dom"

function Permissions({ items, permission, permits }) {
    let {action, id} = useParams()

    const handleChange = (event) => {
        if (event.target.checked) {
            permission('add', event.target.value)    
        } else {
            permission('remove', event.target.value)
        }
    }

    const checkEnabled = (route) => {
        if(permits.length > 0){
            for(let i in permits){
                if(permits[i].name == route){
                    return true
                }
            }
        }
        return false
    }

    return (<>
            <div className='row' key="permissions">
                {items.routes.map((person, index) => (<>
                    <div className='col-lg-12' key={index}>
                        <h5 className='text-capitalize' key={person.name}>{person.name}</h5>
                    </div>
                    {person.routes.map((pet, index2) => (
                        <div className='col-lg-2' key={pet+'-'+index}>
                            <Form.Check checked={checkEnabled(items.key+'.'+[person.name]+'.'+pet+(typeof id == "undefined" ? '.0' : '.'+id)+'.*')} 
                                key={items.key+'.'+[person.name]+']['+(typeof id == "undefined" ? 0 : id)+']'} 
                                onChange={handleChange} 
                                type="checkbox" name={'permissions'} 
                                id={index+index2+pet} 
                                value={items.key+'.'+[person.name]+'.'+pet+(typeof id == "undefined" ? '.0' : '.'+id)+'.*'} for={index+index2+pet} label={pet} />
                        </div>
                    ))}
                </>))}
            </div>
        </>
    );
}

export default Permissions;
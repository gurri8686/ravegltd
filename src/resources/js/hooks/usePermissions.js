import React, { useState, useEffect } from 'react';
import { useSelector, useDispatch } from 'react-redux'

export default function usePermissions(prop) {
    const [data, setData] = useState({})
    const [isOnline, setisOnline] = useState(false)
    const permissions = useSelector((state) => state.PermissionsReducer)

    useEffect(() => {
        let routesArray = [];
        let rolesArray = [];
        let roleStatus = false;

        if(prop == ''){
            setisOnline(false)
        }

        for( let i in permissions.data.current ){
            rolesArray.push(permissions.data.current[i].name)
            if(permissions.data.current[i].name == 'super-admin'){
                roleStatus = true;
            }
        }

        for( let i in permissions.data.current ){
            rolesArray.push(permissions.data.current[i].name)
            for(let k in permissions.data.current[i].permissions){
                routesArray.push(permissions.data.current[i].permissions[k].name)
            }
        }

        if(roleStatus === true){
            setisOnline(true)
        }else{
            if(routesArray.length > 0){
                for( let i in routesArray ){
                    if(routesArray[i] == prop){
                        setisOnline(true)
                    }
                }
            }
        }

        setData(permissions.data)
        
    }, [permissions]);

    return [isOnline,data]
}
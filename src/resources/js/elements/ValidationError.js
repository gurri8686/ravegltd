import React, { useState, useRef, useEffect } from "react";
import Form from 'react-bootstrap/Form';

function ValidationError(props) {

    const checker = () => {
        try {
            let name = (props.name).split('.');
            
            if (name.length == 1) {
                return (<Form.Text className="text-muted" name={props.name}>
                    {props.formik.touched[name[0]] && props.formik.errors[name[0]] ? (
                        <span className="error">{props.formik.errors[name[0]]}</span>) : null}
                </Form.Text>)
            } else if (name.length == 2) {
                console.log(props.formik.touched[name[0]][name[1]])
                return (<Form.Text className="text-muted" name={props.name}>
                    {props.formik.touched[name[0]][name[1]] && props.formik.errors[name[0]][name[1]] ? (
                        <span className="error">{props.formik.errors[name[0]][name[1]]}</span>) : null}
                </Form.Text>)
            } else if (name.length == 3) {
                return (<Form.Text className="text-muted" name={props.name}>
                    {props.formik.touched[name[0]][name[1]][name[2]] && props.formik.errors[name[0]][name[1]][name[2]] ? (
                        <span className="error">{props.formik.errors[name[0]][name[1]][name[2]]}</span>) : null}
                </Form.Text>)
            } else if (name.length == 4) {
                return (<Form.Text className="text-muted" name={props.name}>
                    {props.formik.touched[name[0]][name[1]][name[2]][name[3]] && props.formik.errors[name[0]][name[1]][name[2]][name[3]] ? (
                        <span className="error">{props.formik.errors[name[0]][name[1]][name[2]][name[3]]}</span>) : null}
                </Form.Text>)
            } else if (name.length == 5) {
                return (<Form.Text className="text-muted" name={props.name}>
                    {props.formik.touched[name[0]][name[1]][name[2]][name[3]][name[4]] && props.formik.errors[name[0]][name[1]][name[2]][name[3]][name[4]] ? (
                        <span className="error">{props.formik.errors[name[0]][name[1]][name[2]][name[3]][name[4]]}</span>) : null}
                </Form.Text>)
            }
        }
        catch (err) {
            return <></>
        }
    }

    return (
        <>{checker()}</>
    )
}

export default ValidationError;
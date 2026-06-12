import React, { useState, useEffect, useRef } from "react";
import { Modal, Button, Badge } from "react-bootstrap";
import { Dropzone, FileItem, FullScreenPreview } from "@dropzone-ui/react";
import {CopyToClipboard} from 'react-copy-to-clipboard';
import DataTable from 'react-data-table-component';
import TaskService from './../services/TaskService'

function TinyMCEFileManager(props) {
    var _ = require('lodash');
    const [selectedRows, setSelectedRows] = React.useState([]);
    const [copied, setCopied] = useState(false);
    const [show, setShow] = useState(true);
    const [page, setPage] = useState("upload");
    const [toggleCleared, setToggleCleared] = React.useState(false);
    const [data, setData] = React.useState([]);
    const handleClose = () => { setShow(false); props.closeFM; }
    const handleShow = () => setShow(true);
    const [files, setFiles] = React.useState([]);
    const [imageSrc, setImageSrc] = useState(undefined);

    const updateFiles = (incommingFiles) => {
        console.log("incomming files", incommingFiles);
        setFiles(incommingFiles);
    };
    const onDelete = (id) => {
        setFiles(files.filter((x) => x.id !== id));
    };
    const handleSee = (imageSource) => {
        setImageSrc(imageSource);
    };
    const handleClean = (files) => {
        console.log("list cleaned", files);
    };

    const changPage = (page) => {
        if (page == 'list') {
            /**
             * call ajax to load files.
             */
             TaskService.assets(props.id).then(response => {
                setData(response.data.payload);
            }).catch(error => {

            });
        }
        setPage(page);
    }

    const handleRowSelected = React.useCallback(state => {
        setSelectedRows(state.selectedRows);
    }, []);

    const activateInput = (id) => {
        let newData = [];
        for(let i in data){
            if(data[i].id == id){
                data[i].editMode = 1;
            }else{
                data[i].editMode = 0;
            }
            newData.push(data[i])
        }
        console.log(newData)
        setData(newData);
    }

    const focusOutAll = () => {
        let newData = [];
        for(let i in data){
            data[i].editMode = 0;
            newData.push(data[i])
        }
        setData(newData);
    }

    const updateName = (id, event) => {
        //console.log(id)
        let name = event.target.value
        let newData = [];
        for(let i in data){
            if(data[i].id == id){
                data[i].name = name;
                newData.push(data[i])
            }else{
                newData.push(data[i])
            }
        }
        setData(newData);
    }

    const saveFileName = (id) => {
        console.log(1212);
        let current = "";
        for(let i in data){
            if(data[i].id == id){
                console.log(data[i])
                current = data[i];
            }            
        }
        console.log(current+'=======');
    }

    const contextActions = React.useMemo(() => {
        const handleDelete = () => {
            if (window.confirm(`Are you sure you want to delete:\r ${selectedRows.map(r => r.name)}?`)) {
                setToggleCleared(!toggleCleared);
                //console.log(data)
                //console.log(selectedRows)
                setData(_.differenceBy(data, selectedRows, 'id'));
            }
        };
        return (
            <input type="button" key="delete" onClick={handleDelete} className="btn btn-danger" value="Delete X" />
        );
    }, [data, selectedRows, toggleCleared]);

    useEffect(() => {
        if (show == false) {
            console.log(props)
            props.closeFM();
        }
    }, [show]);

    const columns = [
        {
            name: '#ID',
            selector: row => row.id,
            width: "80px",
            sortable: true,
        },
        {
            name: 'Name',
            width: "220px",
            selector: row => <>
                {typeof row.editMode != "undefined" && row.editMode == 1 
                ?
                    <><input onKeyUp={(event) => updateName(row.id, event)} onBlur={() => focusOutAll()} type="text" name="name" defaultValue={row.name} /></>
                :
                    <span onDoubleClick={() => activateInput(row.id)}>{row.name}</span>
                }
                </>,
            sortable: true,
        },
        {
            name: 'Type',
            width: "80px",
            selector: row => <>{row.extension} </>,
            sortable: true,
        },
        {
            name: 'Size',
            width: "80px",
            selector: row => <>{row.size} </>,
            sortable: true,
        },
        {
            name: 'Last Edited',
            selector: row => row.updated_at,
            sortable: true,
        },
        {
            name: 'Actions',
            selector: row => <>
                {typeof row.editMode != "undefined" && row.editMode == 1 
                ?
                    <><button onClick={() => saveFileName(row.id)} type="button" className="badge badge-primary" style={{fontSize:'14px'}}>
                    Save
                    </button> 
                    | </>
                :
                <>
                <button onClick={() => saveFileName(row.id)} type="button" className="badge badge-primary" style={{fontSize:'14px'}}>
                    Save
                    </button>
                | </>
                }
                <button style={{fontSize:'14px'}} type="button" className="badge badge-primary">Remove</button>
                 |   <CopyToClipboard text={row.id}
                    onCopy={() => setCopied(true)}>
                    <button type="button" className="badge badge-primary" style={{fontSize:'14px'}}>Copy Link</button>
                    </CopyToClipboard>
                </>,
            sortable: false,
        }
    ];

    return (
        <>
            <Modal show={show} onHide={handleClose} dialogClassName="modal-90w" animation={false}>
                <Modal.Header>
                    <Modal.Title>File Manager</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <div className="mb-2">
                        <button type="button" onClick={() => changPage("list")} className="btn btn-warning">Files</button> | <button onClick={() => changPage("upload")} type="button" className="btn btn-success">Upload Files</button>
                    </div>
                    {page == 'upload' ?
                        <Dropzone
                            style={{ minWidth: "550px" }}
                            view="list"
                            children={true}
                            onChange={updateFiles}
                            minHeight="195px"
                            onClean={handleClean}
                            value={files}
                            maxFiles={5}
                            //header={false}
                            // footer={false}
                            maxFileSize={2998000}
                            //label="Drag'n drop files here or click to browse"
                            //label="Suleta tus archivos aquí"
                            //accept=".png,image/*"
                            uploadingMessage={"Uploading..."}
                            url={props.uploadUrl}
                            //of course this url doens´t work, is only to make upload button visible
                            //uploadOnDrop
                            //clickable={false}
                            //fakeUploading
                            //localization={"FR-fr"}
                            disableScroll
                        >
                            {files.map((file) => (
                                <FileItem
                                    {...file}
                                    key={file.id}
                                    onDelete={onDelete}
                                    onSee={handleSee}
                                    //localization={"ES-es"}
                                    resultOnTooltip
                                    preview
                                    info
                                    hd
                                />
                            ))}
                            <FullScreenPreview
                                imgSource={imageSrc}
                                openImage={imageSrc}
                                onClose={(e) => handleSee(undefined)}
                            />
                        </Dropzone>
                        : ""
                    }
                    {page == 'list' ?
                        <DataTable
                            title="Attached Files"
                            pagination
                            columns={columns}
                            data={data}
                            selectableRows
                            contextActions={contextActions}
                            onSelectedRowsChange={handleRowSelected}
                            clearSelectedRows={toggleCleared}
                            defaultSortFieldId={1}
                        />
                        : ""}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={handleClose}>
                        Close
                    </Button>

                </Modal.Footer>
            </Modal>
        </>
    )
}

export default TinyMCEFileManager;
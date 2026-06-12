import React, { useState, useRef } from "react";
import { Editor } from '@tinymce/tinymce-react';
import TinyMCEFileManager from './TinyMCEFileManager';

function FullFeatured(props) {
    const editorRef = useRef(null);
    const [fM, setFM] = useState(false);

    const log = () => {
        if (editorRef.current) {
            props.editor(editorRef.current.getContent())
        }
    };

    const closeFM = () => {
        setFM(current => !current);
    }

    const toggleSetFM = () => {
        setFM(current => !current);
    }

    return (
        <>
        <button type="button" id="fileManager" onClick={toggleSetFM} style={{display:'none'}}></button>
        {fM === true ? <TinyMCEFileManager closeFM={closeFM} uploadUrl={props.uploadUrl} id={props.id}/> : <></>}
        <Editor
            apiKey='ltxendzxpldibfppw60rgwkkysd3qy93ct9k3ghsndugfj4x'
            tinymceScriptSrc={process.env.TINYMCE_URL_MIN}
            onSelectionChange={log}
            onInit={(evt, editor) => editorRef.current = editor}
            value={props.initialValue}
            init={{
                //custom_variable : "test"+(new Date()).getTime(),
                pageProps : {
                    'file-manager-id' : 'fileManager'
                },
                height: 500,
                plugins: [
                  'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'media',
                  'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                  'insertdatetime', 'media', 'table', 'preview', 'help', 'wordcount','filemanager'
                ],
                menubar: 'file edit view insert insertfile format tools table tc help filemanager',
                toolbar: 'undo redo | filemanager | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist checklist | forecolor backcolor casechange permanentpen formatpainter removeformat | pagebreak | charmap emoticons | insertfile image media pageembed template link codesample | a11ycheck ltr rtl | showcomments addcomment | fullscreen  preview save print',
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
            }}
        />
        </>
    )
}

export { FullFeatured }

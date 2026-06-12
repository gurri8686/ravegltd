import { Routes, Route, Link as ReactLink, MemoryRouter } from "react-router-dom";
import { changeCurrentAction } from './../actions/ActionsActions'

function BreadCrumbs({ actions }) {
    const _l = actions.length;
    return (<>
        <div className="row">
            <div className="col-md-12">
                {actions.length > 0 ?
                    <>
                        {actions.map((a, i) => (
                            <ReactLink
                                onClick={() => dispatch(changeCurrentAction(
                                    {
                                        type: a.type,
                                        task: '',
                                        parent: a.id,
                                        id: a.id,
                                    }
                                ))}
                                to={'/detail' + '/' + a.type + (typeof a.id != "undefined" ? '/' + a.id : '')}>
                                <><span key={i+'_link'} className="badge badge-primary text-uppercase">{a.title != "" ? a.title : a.subject} - {(a.type).replace(/_/g, ' ')}</span> {i < (_l - 1) ? '/' : ''} </>
                            </ReactLink>
                        ))}
                    </> : <></>}
            </div>
        </div>
    </>)
}

export default BreadCrumbs;
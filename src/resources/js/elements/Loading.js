//import "react-loader-spinner/dist/loader/css/react-spinner-loader.css";
import { BallTriangle } from 'react-loader-spinner'

function Loading() {
    return (<>
        <div className="row">
            <div className="col-md-12 d-flex justify-content-center">
                <div className="error-template center mt-5 mb-5">
                    <BallTriangle
                        height="100"
                        width="100"
                        color="#f97316"
                        ariaLabel="loading-indicator"
                    />
                    <p className="mt-5 text-center" style={{color:'#f97316',fontWeight:'700',letterSpacing:'1.5px',fontSize:'13px'}}>LOADING...</p>
                </div>
            </div>
        </div>
    </>)
}

export default Loading;

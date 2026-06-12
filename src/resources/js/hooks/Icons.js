// Icons.js
/**
 * import Icon from "./../hooks/Icons";	
 * <Icon name="copy" className="text-warning ms-2" />
 */
import React from "react";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";

// Solid Icons
import {
  faCopy,
  faTrash,
  faEdit,
  faPlus,
  faPrint,
  faFilePdf,
  faFileExcel,
  faFileCsv,
  faTriangleExclamation, // warning / danger triangle
  faCheck,          // success tick
  faSearch,
  faGear
} from "@fortawesome/free-solid-svg-icons";

// Regular Icons
import { faEnvelope, faCommentDots } from "@fortawesome/free-regular-svg-icons";

// Brand Icons
import { faWhatsapp } from "@fortawesome/free-brands-svg-icons";

// Map icon names to FontAwesome icons
const iconMap = {
  copy: faCopy,
  trash: faTrash,
  edit: faEdit,
  add: faPlus,

  print: faPrint,
  email: faEnvelope,
  sms: faCommentDots,
  whatsapp: faWhatsapp,

  pdf: faFilePdf,
  excel: faFileExcel,
  csv: faFileCsv,

  // You requested:
  warning_triangle: faTriangleExclamation,
  danger_triangle: faTriangleExclamation, // same icon but styled red
  success_tick: faCheck,
  search: faSearch,
  settings: faGear,
};

const Icon = ({ name, ...props }) => {
  const icon = iconMap[name];

  if (!icon) {
    console.warn(`⚠️ Icon "${name}" not found in Icons.js`);
    return null;
  }

  return <FontAwesomeIcon icon={icon} {...props} />;
};

export default Icon;

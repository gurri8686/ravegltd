export const orangeSelectStyles = {
  control: (base, state) => ({
    ...base,
    borderColor: state.isFocused ? 'rgb(234, 88, 12)' : '#ced4da',
    boxShadow: state.isFocused ? '0 0 0 0.2rem rgba(249,115,22,0.25)' : 'none',
    '&:hover': { borderColor: 'rgb(234, 88, 12)' },
  }),
  option: (base, state) => ({
    ...base,
    backgroundColor: state.isSelected ? 'rgb(234, 88, 12)' : state.isFocused ? '#fff3e0' : '#fff',
    color: state.isSelected ? '#fff' : '#333',
    cursor: 'pointer',
  }),
};

export const fixedSelectStyles = ({
  width = "100%",
  maxWidth = "300px",
  isMulti = false,
} = {}) => ({
  container: (base) => ({
    ...base,
    width,
    maxWidth,
  }),
  control: (base, state) => ({
    ...base,
    minHeight: 'calc(2.75rem + 2px)',
    height: 'calc(2.75rem + 2px)',
    borderColor: state.isFocused ? 'rgb(234, 88, 12)' : '#ced4da',
    boxShadow: state.isFocused ? '0 0 0 0.2rem rgba(249,115,22,0.25)' : 'none',
    '&:hover': { borderColor: 'rgb(234, 88, 12)' },
  }),
  valueContainer: (base) => ({
    ...base,
    height: 'calc(2.75rem + 2px)',
    padding: '0 8px',
    overflow: "hidden",
    flexWrap: isMulti ? "wrap" : "nowrap",
  }),
  input: (base) => ({
    ...base,
    minWidth: 0,
    width: "100%",
    margin: 0,
    padding: 0,
  }),
  indicatorsContainer: (base) => ({
    ...base,
    height: 'calc(2.75rem + 2px)',
  }),
  option: (base, state) => ({
    ...base,
    backgroundColor: (!state.data.value)
      ? 'transparent'
      : state.isSelected
        ? 'rgb(234, 88, 12)'
        : state.isFocused
          ? '#fff7ed'
          : 'transparent',
    color: (!state.data.value) ? '#aaa' : state.isSelected ? '#ffffff' : '#404e67',
    cursor: state.data.value ? 'pointer' : 'default',
    '&:active': {
      backgroundColor: state.data.value ? 'rgb(234, 88, 12)' : 'transparent',
      color: state.data.value ? '#ffffff' : '#aaa',
    },
  }),
});

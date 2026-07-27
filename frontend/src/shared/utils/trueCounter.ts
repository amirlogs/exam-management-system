type WorkspaceState = {
  admin: boolean
  instructor: boolean
  student: boolean
}

const roles: (keyof WorkspaceState)[] = ['admin', 'instructor', 'student']

export default function trueCouter(workspaceState: WorkspaceState) {
  let count = 0
  let singleRoute = ''
  for (const role of roles) {
    if (workspaceState[role]) {
      singleRoute = role
      count = count + 1
    }
  }
  if (count === 1) {
    return singleRoute
  } else {
    return workspaceState
  }
}

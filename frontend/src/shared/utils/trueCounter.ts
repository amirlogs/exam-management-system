type WorkspaceState = {
  admin: boolean;
  instructor: boolean;
  student: boolean;
};

const roles: (keyof WorkspaceState)[] = ['admin', 'instructor', 'student'];

export function trueCouter(workspaceState: WorkspaceState) {
  let count = 0;
  let singleRoute = '';
  for (const role of roles) {
    if (workspaceState[role]) {
      singleRoute = role;
      count = count + 1;
    }
  }

  return count;
}

export function getSingleRoute(workspaceState: WorkspaceState) {
  let singleRoute = '';
  for (const role of roles) {
    if (workspaceState[role]) {
      singleRoute = role;
    }
  }

  return singleRoute;
}

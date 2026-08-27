<script setup lang="ts">
import { Building2, GraduationCap, Users, BookOpen, ClipboardList, CalendarDays, Clock, ArrowUpRight } from 'lucide-vue-next';
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-text">Dashboard</h1>

      <p class="mt-1 text-sm text-text/60">Overview of the university examination system.</p>
    </div>

    <!-- Overview cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-surface border border-border rounded-xl p-5">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm text-text/60">Universities</p>

            <p class="mt-2 text-2xl font-semibold text-text">4</p>
          </div>

          <div class="p-2.5 rounded-lg bg-accent/10 text-accent">
            <Building2 class="w-5 h-5" />
          </div>
        </div>

        <p class="mt-4 text-xs text-text/50">Across the system</p>
      </div>

      <div class="bg-surface border border-border rounded-xl p-5">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm text-text/60">Departments</p>

            <p class="mt-2 text-2xl font-semibold text-text">28</p>
          </div>

          <div class="p-2.5 rounded-lg bg-accent/10 text-accent">
            <GraduationCap class="w-5 h-5" />
          </div>
        </div>

        <p class="mt-4 text-xs text-text/50">Active departments</p>
      </div>

      <div class="bg-surface border border-border rounded-xl p-5">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm text-text/60">Users</p>

            <p class="mt-2 text-2xl font-semibold text-text">1,284</p>
          </div>

          <div class="p-2.5 rounded-lg bg-accent/10 text-accent">
            <Users class="w-5 h-5" />
          </div>
        </div>

        <p class="mt-4 text-xs text-text/50">Registered users</p>
      </div>

      <div class="bg-surface border border-border rounded-xl p-5">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm text-text/60">Courses</p>

            <p class="mt-2 text-2xl font-semibold text-text">342</p>
          </div>

          <div class="p-2.5 rounded-lg bg-accent/10 text-accent">
            <BookOpen class="w-5 h-5" />
          </div>
        </div>

        <p class="mt-4 text-xs text-text/50">Available courses</p>
      </div>
    </div>

    <!-- Main grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Upcoming exams -->
      <div class="lg:col-span-2 bg-surface border border-border rounded-xl">
        <div class="flex items-center justify-between px-5 py-4 border-b border-border">
          <div>
            <h2 class="font-semibold text-text">Upcoming Exams</h2>

            <p class="text-xs text-text/50 mt-1">Recently scheduled examinations</p>
          </div>

          <button class="flex items-center gap-1 text-sm text-accent hover:underline">
            View all
            <ArrowUpRight class="w-4 h-4" />
          </button>
        </div>

        <div class="divide-y divide-border">
          <div class="flex items-center justify-between px-5 py-4">
            <div class="flex items-center gap-3">
              <div class="p-2 rounded-lg bg-accent/10 text-accent">
                <ClipboardList class="w-4 h-4" />
              </div>

              <div>
                <p class="text-sm font-medium text-text">Software Engineering</p>

                <p class="text-xs text-text/50 mt-1">Final Examination · Section A</p>
              </div>
            </div>

            <div class="text-right">
              <p class="text-sm font-medium text-text">Aug 18</p>

              <p class="text-xs text-text/50 mt-1">09:00 AM</p>
            </div>
          </div>

          <div class="flex items-center justify-between px-5 py-4">
            <div class="flex items-center gap-3">
              <div class="p-2 rounded-lg bg-accent/10 text-accent">
                <ClipboardList class="w-4 h-4" />
              </div>

              <div>
                <p class="text-sm font-medium text-text">Database Systems</p>

                <p class="text-xs text-text/50 mt-1">Midterm Examination · Section B</p>
              </div>
            </div>

            <div class="text-right">
              <p class="text-sm font-medium text-text">Aug 20</p>

              <p class="text-xs text-text/50 mt-1">02:00 PM</p>
            </div>
          </div>

          <div class="flex items-center justify-between px-5 py-4">
            <div class="flex items-center gap-3">
              <div class="p-2 rounded-lg bg-accent/10 text-accent">
                <ClipboardList class="w-4 h-4" />
              </div>

              <div>
                <p class="text-sm font-medium text-text">Computer Networks</p>

                <p class="text-xs text-text/50 mt-1">Final Examination · Section A</p>
              </div>
            </div>

            <div class="text-right">
              <p class="text-sm font-medium text-text">Aug 23</p>

              <p class="text-xs text-text/50 mt-1">09:00 AM</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick overview -->
      <div class="bg-surface border border-border rounded-xl">
        <div class="px-5 py-4 border-b border-border">
          <h2 class="font-semibold text-text">Examination Overview</h2>

          <p class="text-xs text-text/50 mt-1">Current examination activity</p>
        </div>

        <div class="p-5 space-y-5">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="p-2 rounded-lg bg-accent/10 text-accent">
                <ClipboardList class="w-4 h-4" />
              </div>

              <span class="text-sm text-text"> Draft </span>
            </div>

            <span class="font-semibold text-text"> 12 </span>
          </div>

          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="p-2 rounded-lg bg-accent/10 text-accent">
                <Clock class="w-4 h-4" />
              </div>

              <span class="text-sm text-text"> Pending Approval </span>
            </div>

            <span class="font-semibold text-text"> 7 </span>
          </div>

          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="p-2 rounded-lg bg-accent/10 text-accent">
                <CalendarDays class="w-4 h-4" />
              </div>

              <span class="text-sm text-text"> Scheduled </span>
            </div>

            <span class="font-semibold text-text"> 15 </span>
          </div>

          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="p-2 rounded-lg bg-accent/10 text-accent">
                <ClipboardList class="w-4 h-4" />
              </div>

              <span class="text-sm text-text"> Completed </span>
            </div>

            <span class="font-semibold text-text"> 86 </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent activity -->
    <div class="bg-surface border border-border rounded-xl">
      <div class="px-5 py-4 border-b border-border">
        <h2 class="font-semibold text-text">Recent Activity</h2>

        <p class="text-xs text-text/50 mt-1">Latest activity in the system</p>
      </div>

      <div class="divide-y divide-border">
        <div class="flex items-center gap-4 px-5 py-4">
          <div class="w-2 h-2 rounded-full bg-accent" />

          <div class="flex-1">
            <p class="text-sm text-text">New examination was scheduled</p>

            <p class="text-xs text-text/50 mt-1">Software Engineering · Final Examination</p>
          </div>

          <span class="text-xs text-text/50"> 10 min ago </span>
        </div>

        <div class="flex items-center gap-4 px-5 py-4">
          <div class="w-2 h-2 rounded-full bg-accent" />

          <div class="flex-1">
            <p class="text-sm text-text">New course was created</p>

            <p class="text-xs text-text/50 mt-1">Advanced Database Systems</p>
          </div>

          <span class="text-xs text-text/50"> 1 hour ago </span>
        </div>

        <div class="flex items-center gap-4 px-5 py-4">
          <div class="w-2 h-2 rounded-full bg-accent" />

          <div class="flex-1">
            <p class="text-sm text-text">Curriculum was updated</p>

            <p class="text-xs text-text/50 mt-1">Software Engineering Program</p>
          </div>

          <span class="text-xs text-text/50"> 3 hours ago </span>
        </div>
      </div>
    </div>
  </div>
</template>
